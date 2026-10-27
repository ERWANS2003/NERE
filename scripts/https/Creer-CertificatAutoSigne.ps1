<#
.SYNOPSIS
    Cree le certificat auto-signe de l'intranet (noms + adresse IP) et le met en service sur IIS.

.DESCRIPTION
    A lancer sur le serveur, en administrateur (creation initiale ou renouvellement avant expiration).
      1. Sauvegarde la configuration IIS et le .env.
      2. Cree un certificat RSA 2048 / SHA-256 valable 2 ans pour l'IP et les noms du serveur
         (2 ans : duree maximale acceptee par tous les systemes pour un certificat serveur).
      3. L'attache au port 443.
      4. Redirige tout acces (HTTP, IP ou nom court) vers https://<premier nom>/.
      5. Met APP_URL a jour et reconstruit le cache de configuration Laravel.
      6. Exporte le certificat public (.cer) a deployer sur les postes
         (Deployer-CertificatGPO.ps1 ou Installer-CertificatClient.ps1).

.EXAMPLE
    .\Creer-CertificatAutoSigne.ps1
#>
[CmdletBinding()]
param(
    [string]$Ip = '192.168.10.206',

    # Le premier nom est l'adresse officielle de l'intranet (enregistrement A dans le DNS du domaine).
    [string[]]$Noms = @('OUA-NM-INTRANET.neremining.com', 'OUA-NM-INTRANET'),

    [string]$AppPath = 'C:\inetpub\wwwroot\itsm-nere-mining',

    [string]$Export = 'C:\Install\intranet-nere-mining-ip.cer',

    [int]$Jours = 730
)

$ErrorActionPreference = 'Stop'
$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}
Import-Module WebAdministration
$site = @(Get-Website | Where-Object { $_.physicalPath -like '*itsm-nere-mining*' })[0].Name
if (-not $site) { throw 'Site IIS de l''intranet introuvable.' }
$horodatage = Get-Date -Format 'yyyyMMdd-HHmmss'
$hote = $Noms[0]

Write-Host '==> Sauvegardes' -ForegroundColor Cyan
& "$env:windir\System32\inetsrv\appcmd.exe" add backup "avant-cert-ip-$horodatage" | Out-Null
Copy-Item "$AppPath\.env" "$AppPath\.env.avant-cert-ip-$horodatage"
Write-Host "    IIS : appcmd restore backup avant-cert-ip-$horodatage"
Write-Host "    .env : $AppPath\.env.avant-cert-ip-$horodatage"

Write-Host '==> Certificat' -ForegroundColor Cyan
$san = "IPAddress=$Ip" + (($Noms | ForEach-Object { "&DNS=$_" }) -join '')
$cert = New-SelfSignedCertificate `
    -Subject "CN=$Ip, O=NERE Mining, OU=Intranet" `
    -FriendlyName 'Intranet NERE Mining (auto-signe IP)' `
    -TextExtension @("2.5.29.17={text}$san", '2.5.29.37={text}1.3.6.1.5.5.7.3.1') `
    -KeyAlgorithm RSA -KeyLength 2048 -HashAlgorithm SHA256 `
    -KeyUsage DigitalSignature, KeyEncipherment -KeyExportPolicy Exportable `
    -NotAfter (Get-Date).AddDays($Jours) `
    -CertStoreLocation 'Cert:\LocalMachine\My'
Write-Host "    $($cert.Thumbprint) | expire le $($cert.NotAfter.ToString('dd/MM/yyyy'))" -ForegroundColor Green

Write-Host '==> Liaison 443' -ForegroundColor Cyan
Remove-Item 'IIS:\SslBindings\0.0.0.0!443' -ErrorAction SilentlyContinue
New-Item 'IIS:\SslBindings\0.0.0.0!443' -Thumbprint $cert.Thumbprint -SSLFlags 0 -Store My | Out-Null
# Pas de demande de certificat client : elle provoque une renegociation TLS refusee par les navigateurs.
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter 'system.webServer/security/access' -Name sslFlags -Value 'None'
if (-not (Get-WebBinding -Name $site -Protocol https -Port 443)) {
    New-WebBinding -Name $site -Protocol https -Port 443
}

Write-Host "==> Redirection vers https://$hote/" -ForegroundColor Cyan
$section = 'system.webServer/rewrite/rules'
$nom = 'NERE - Forcer HTTPS'
$filtre = "$section/rule[@name='$nom']"
$hoteRegex = [regex]::Escape($hote)
Clear-WebConfiguration -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter $filtre -ErrorAction SilentlyContinue
Add-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter $section -Name '.' -AtIndex 0 -Value @{ name = $nom; stopProcessing = 'True' }
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/match" -Name 'url' -Value '(.*)'
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/conditions" -Name 'logicalGrouping' -Value 'MatchAny'
Add-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/conditions" -Name '.' -Value @{ input = '{HTTPS}'; pattern = '^OFF$' }
Add-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/conditions" -Name '.' -Value @{ input = '{HTTP_HOST}'; pattern = "^$hoteRegex$"; negate = 'True' }
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/action" -Name 'type' -Value 'Redirect'
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/action" -Name 'url' -Value "https://$hote/{R:1}"
Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $site -Filter "$filtre/action" -Name 'redirectType' -Value 'Permanent'

Write-Host '==> Laravel' -ForegroundColor Cyan
$lignes = [IO.File]::ReadAllLines("$AppPath\.env")
$valeurs = [ordered]@{ 'APP_URL' = "https://$hote"; 'SESSION_SECURE_COOKIE' = 'true' }
foreach ($cle in $valeurs.Keys) {
    $trouve = $false
    for ($i = 0; $i -lt $lignes.Length; $i++) {
        if ($lignes[$i] -match "^\s*$cle=") { $lignes[$i] = "$cle=$($valeurs[$cle])"; $trouve = $true }
    }
    if (-not $trouve) { $lignes += "$cle=$($valeurs[$cle])" }
}
[IO.File]::WriteAllLines("$AppPath\.env", $lignes, (New-Object Text.UTF8Encoding($false)))
$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if ($php) {
    Push-Location $AppPath
    try { & $php artisan config:cache | Out-Null } finally { Pop-Location }
    Write-Host "    APP_URL=https://$hote, cache reconstruit" -ForegroundColor Green
} else {
    Write-Host "    !! Lancez 'php artisan config:cache' dans $AppPath" -ForegroundColor Yellow
}

Write-Host '==> Export du certificat public' -ForegroundColor Cyan
New-Item -ItemType Directory -Force (Split-Path $Export -Parent) | Out-Null
Export-Certificate -Cert $cert -FilePath $Export -Type CERT | Out-Null
Write-Host "    $Export (a deployer sur les postes ; ne contient pas la cle privee)" -ForegroundColor Green
Write-Host "`nIntranet : https://$hote" -ForegroundColor Green
