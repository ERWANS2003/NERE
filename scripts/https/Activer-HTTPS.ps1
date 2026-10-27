<#
.SYNOPSIS
    Active le HTTPS de l'intranet NERE Mining sur IIS avec un certificat .pfx.

.DESCRIPTION
    A lancer sur le serveur, dans une console PowerShell "Executer en tant qu'administrateur".
      1. Importe le certificat dans le magasin de l'ordinateur (LocalMachine\My).
      2. Ajoute la liaison HTTPS (port 443) au site IIS et y attache le certificat.
      3. Ouvre le port 443 dans le pare-feu Windows.
      4. Redirige tout le trafic HTTP vers HTTPS (regle stockee dans applicationHost.config,
         donc non ecrasee par un "git pull" du web.config).
      5. Met a jour le .env de Laravel (APP_URL en https, cookie de session securise),
         apres sauvegarde, puis reconstruit le cache de configuration.
      6. Redemarre le site et teste l'URL HTTPS.
      7. Exporte la partie publique du certificat (.cer) a distribuer aux postes clients.

    Le mot de passe du .pfx est demande au clavier : il n'est ni affiche ni enregistre.

.EXAMPLE
    .\Activer-HTTPS.ps1 -PfxPath "C:\Install\intranet-cert.pfx"

.EXAMPLE
    .\Activer-HTTPS.ps1 -PfxPath "C:\Install\intranet-cert.pfx" -SiteName "ITSM-NERE-Mining" -HostName "UA-NM-INTRANET.neremining.com"
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$PfxPath,

    [string]$SiteName = 'ITSM-NERE-Mining',

    [string]$HostName = 'UA-NM-INTRANET.neremining.com',

    [int]$Port = 443,

    # Dossier racine de l'application Laravel (celui qui contient .env et artisan).
    # Par defaut : deduit du chemin physique du site IIS.
    [string]$AppPath,

    [switch]$SansRedirection
)

$ErrorActionPreference = 'Stop'

function Etape([string]$texte) { Write-Host "`n==> $texte" -ForegroundColor Cyan }
function Ok([string]$texte) { Write-Host "    OK  $texte" -ForegroundColor Green }
function Attention([string]$texte) { Write-Host "    !!  $texte" -ForegroundColor Yellow }

# --- Pre-requis -------------------------------------------------------------
$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}
if (-not (Test-Path -LiteralPath $PfxPath)) {
    throw "Certificat introuvable : $PfxPath"
}
Import-Module WebAdministration
$site = Get-Website -Name $SiteName
if (-not $site) {
    throw "Site IIS '$SiteName' introuvable. Sites existants : $((Get-Website).Name -join ', ')"
}

$cheminPhysique = [Environment]::ExpandEnvironmentVariables($site.physicalPath)
if (-not $AppPath) {
    $AppPath = if ((Split-Path $cheminPhysique -Leaf) -eq 'public') { Split-Path $cheminPhysique -Parent } else { $cheminPhysique }
}
if ((Split-Path $cheminPhysique -Leaf) -ne 'public') {
    Attention "Le site pointe sur '$cheminPhysique' et non sur le dossier 'public' de Laravel."
    Attention 'Recommande : faire pointer le site sur <application>\public pour ne jamais exposer .env ni le code.'
}
$fichierEnv = Join-Path $AppPath '.env'
if (-not (Test-Path -LiteralPath $fichierEnv)) {
    throw "Fichier .env introuvable dans '$AppPath'. Precisez -AppPath."
}

# --- 1. Import du certificat ------------------------------------------------
Etape 'Import du certificat dans LocalMachine\My'
$motDePasse = Read-Host -AsSecureString "Mot de passe du fichier $([IO.Path]::GetFileName($PfxPath))"
$certificat = Import-PfxCertificate -FilePath $PfxPath -CertStoreLocation 'Cert:\LocalMachine\My' -Password $motDePasse
Ok "Empreinte : $($certificat.Thumbprint)"
Ok "Sujet     : $($certificat.Subject)"
Ok "Valide du $($certificat.NotBefore.ToString('dd/MM/yyyy')) au $($certificat.NotAfter.ToString('dd/MM/yyyy'))"

if (-not $certificat.HasPrivateKey) {
    throw 'Le certificat importe ne contient pas de cle privee : IIS ne pourra pas l''utiliser.'
}
if ($certificat.NotAfter -lt (Get-Date).AddDays(30)) {
    Attention "Le certificat expire dans moins de 30 jours ($($certificat.NotAfter.ToString('dd/MM/yyyy')))."
}
$nomsCouverts = @($certificat.DnsNameList | ForEach-Object { $_.Unicode })
if ($nomsCouverts -notcontains $HostName) {
    Attention "Le certificat couvre : $($nomsCouverts -join ', '). Il ne couvre pas '$HostName' : le navigateur affichera une alerte."
}
if ($certificat.Subject -eq $certificat.Issuer) {
    Attention 'Certificat auto-signe : il faudra l''approuver sur les postes clients (voir Installer-CertificatClient.ps1).'
}

# --- 2. Liaison HTTPS -------------------------------------------------------
Etape "Liaison HTTPS ${HostName}:$Port sur le site '$SiteName'"
$avecSni = -not [string]::IsNullOrWhiteSpace($HostName)
Get-WebBinding -Name $SiteName -Protocol https -Port $Port -ErrorAction SilentlyContinue | ForEach-Object {
    Remove-WebBinding -Name $SiteName -Protocol https -Port $Port -HostHeader $_.bindingInformation.Split(':')[2]
    Attention "Ancienne liaison HTTPS supprimee : $($_.bindingInformation)"
}
New-WebBinding -Name $SiteName -Protocol https -Port $Port -HostHeader $HostName -SslFlags ($(if ($avecSni) { 1 } else { 0 }))
$liaison = Get-WebBinding -Name $SiteName -Protocol https -Port $Port -HostHeader $HostName
$liaison.AddSslCertificate($certificat.Thumbprint, 'my')
Ok "https://${HostName}:$Port lie au certificat"

$liaisonHttpAvecHote = Get-WebBinding -Name $SiteName -Protocol http | Where-Object { $_.bindingInformation -like "*:$HostName" }
if (-not $liaisonHttpAvecHote) {
    Attention "Aucune liaison HTTP n'utilise le nom '$HostName' : les anciens liens http://<IP> continueront de fonctionner puis seront rediriges."
}

# --- 3. Pare-feu ------------------------------------------------------------
Etape "Pare-feu : port $Port"
$regle = "Intranet NERE - HTTPS ($Port)"
if (-not (Get-NetFirewallRule -DisplayName $regle -ErrorAction SilentlyContinue)) {
    New-NetFirewallRule -DisplayName $regle -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow | Out-Null
    Ok "Regle '$regle' creee"
} else {
    Ok "Regle '$regle' deja presente"
}

# --- 4. Redirection HTTP -> HTTPS ------------------------------------------
if (-not $SansRedirection) {
    Etape 'Redirection HTTP vers HTTPS'
    $section = 'system.webServer/rewrite/rules'
    $nomRegle = 'NERE - Forcer HTTPS'
    $filtre = "$section/rule[@name='$nomRegle']"
    if (-not (Test-Path -LiteralPath "$env:SystemRoot\System32\inetsrv\rewrite.dll")) {
        Attention 'Module IIS URL Rewrite absent : redirection non configuree. Installez-le puis relancez le script.'
    } else {
        Clear-WebConfiguration -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter $filtre -ErrorAction SilentlyContinue
        Add-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter $section -Name '.' -AtIndex 0 -Value @{
            name = $nomRegle; stopProcessing = 'True'
        }
        Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter "$filtre/match" -Name 'url' -Value '(.*)'
        Add-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter "$filtre/conditions" -Name '.' -Value @{
            input = '{HTTPS}'; pattern = '^OFF$'
        }
        Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter "$filtre/action" -Name 'type' -Value 'Redirect'
        Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter "$filtre/action" -Name 'url' -Value "https://$HostName/{R:1}"
        Set-WebConfigurationProperty -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName -Filter "$filtre/action" -Name 'redirectType' -Value 'Permanent'
        Ok "http://... -> https://$HostName/ (redirection permanente)"
    }
}

# --- 5. Configuration Laravel ----------------------------------------------
Etape "Mise a jour de $fichierEnv"
$sauvegarde = "$fichierEnv.avant-https-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Copy-Item -LiteralPath $fichierEnv -Destination $sauvegarde
Ok "Sauvegarde : $sauvegarde"

$lignes = [IO.File]::ReadAllLines($fichierEnv)
$valeurs = [ordered]@{
    'APP_URL' = "https://$HostName"
    'SESSION_SECURE_COOKIE' = 'true'
}
foreach ($cle in $valeurs.Keys) {
    $trouve = $false
    for ($i = 0; $i -lt $lignes.Length; $i++) {
        if ($lignes[$i] -match "^\s*$cle=") {
            $lignes[$i] = "$cle=$($valeurs[$cle])"
            $trouve = $true
        }
    }
    if (-not $trouve) { $lignes += "$cle=$($valeurs[$cle])" }
    Ok "$cle=$($valeurs[$cle])"
}
[IO.File]::WriteAllLines($fichierEnv, $lignes, (New-Object Text.UTF8Encoding($false)))

$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php) {
    $handler = Get-WebConfiguration -PSPath "IIS:\Sites\$SiteName" -Filter 'system.webServer/handlers/add' | Where-Object { $_.path -eq '*.php' } | Select-Object -First 1
    if ($handler) { $php = $handler.scriptProcessor -replace 'php-cgi\.exe$', 'php.exe' }
}
if ($php -and (Test-Path -LiteralPath $php)) {
    Push-Location $AppPath
    try {
        & $php artisan config:clear | Out-Null
        & $php artisan config:cache | Out-Null
        Ok 'Cache de configuration Laravel reconstruit'
    } finally {
        Pop-Location
    }
} else {
    Attention "php.exe introuvable : lancez 'php artisan config:cache' dans $AppPath."
}

# --- 6. Redemarrage et test ------------------------------------------------
Etape 'Redemarrage du site et test'
Stop-Website -Name $SiteName
Start-Website -Name $SiteName
Ok "Site '$SiteName' redemarre"

$url = "https://$HostName/"
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
    $requete = [Net.HttpWebRequest]::Create($url)
    $requete.AllowAutoRedirect = $false
    $requete.Timeout = 15000
    # Test local uniquement : le certificat auto-signe n'est pas encore approuve sur le serveur.
    $requete.ServerCertificateValidationCallback = { $true }
    $reponse = $requete.GetResponse()
    Ok "$url repond (HTTP $([int]$reponse.StatusCode))"
    $reponse.Close()
} catch [Net.WebException] {
    if ($_.Exception.Response) {
        Ok "$url repond (HTTP $([int]$_.Exception.Response.StatusCode))"
    } else {
        Attention "Test de $url impossible : $($_.Exception.Message)"
        Attention "Verifiez que '$HostName' est resolu par le DNS (ou le fichier hosts) vers ce serveur."
    }
}

# --- 7. Export du certificat public ----------------------------------------
Etape 'Export du certificat public pour les postes clients'
$cer = Join-Path (Split-Path $PfxPath -Parent) 'intranet-nere-mining.cer'
Export-Certificate -Cert $certificat -FilePath $cer -Type CERT | Out-Null
Ok "Certificat public (sans cle privee) : $cer"

Write-Host "`nTermine. Adresse de l'intranet : https://$HostName" -ForegroundColor Green
Write-Host "Restauration possible avec : .\Desactiver-HTTPS.ps1 -EnvBackup `"$sauvegarde`"" -ForegroundColor DarkGray
