<#
.SYNOPSIS
    Etape 2/2 : obtient (ou renouvelle) le certificat Let's Encrypt et le pose sur IIS.

.DESCRIPTION
    A lancer en administrateur apres LetsEncrypt-Preparer.ps1 et la creation du CNAME.
      1. Verifie que le CNAME _acme-challenge est visible dans le DNS public.
      2. Demande le certificat a Let's Encrypt (validation DNS via acme-dns).
         Utiliser ce script vaut acceptation des conditions d'utilisation de Let's Encrypt.
      3. Importe le certificat dans LocalMachine\WebHosting et l'attache au port 443 d'IIS.
      4. Cree la tache planifiee quotidienne "NERE - Renouvellement certificat HTTPS"
         (compte SYSTEM) qui relance ce script avec -Renouvellement.

    Le certificat dure 90 jours ; Posh-ACME le renouvelle a partir de 60 jours.

.EXAMPLE
    .\LetsEncrypt-Emettre.ps1

.EXAMPLE
    .\LetsEncrypt-Emettre.ps1 -Test     # autorite de test Let's Encrypt (certificat non reconnu)
#>
[CmdletBinding()]
param(
    [string]$HostName = 'OUA-NM-INTRANET.neremining.com',

    [switch]$Test,

    # Mode utilise par la tache planifiee : ne fait rien si le certificat n'est pas a renouveler.
    [switch]$Renouvellement
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$dossier = 'C:\ProgramData\NereAcme'
$journal = Join-Path $dossier 'renouvellement.log'
$env:POSHACME_HOME = $dossier

function Trace([string]$texte, [string]$couleur = 'Gray') {
    $ligne = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')  $texte"
    Add-Content -Path $journal -Value $ligne -Encoding ascii
    Write-Host $ligne -ForegroundColor $couleur
}

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
Import-Module Posh-ACME
Import-Module WebAdministration

$inscription = Get-Content (Join-Path $dossier 'acme-dns.json') -Raw | ConvertFrom-Json
$pluginArgs = @{
    ACMEServer = $inscription.server
    ACMERegistration = @{
        $inscription.challenge = @($inscription.subdomain, $inscription.username, $inscription.password, $inscription.fulldomain)
    }
}

Set-PAServer $(if ($Test) { 'LE_STAGE' } else { 'LE_PROD' })

if ($Renouvellement) {
    $certificat = Submit-Renewal -MainDomain $HostName -PluginArgs $pluginArgs
    if (-not $certificat) {
        Trace 'Pas de renouvellement necessaire.'
        return
    }
    Trace "Certificat renouvele, expire le $($certificat.NotAfter)" 'Green'
} else {
    $cible = (Resolve-DnsName "_acme-challenge.$HostName" -Type CNAME -Server 1.1.1.1 -ErrorAction SilentlyContinue).NameHost
    if ($cible -ne $inscription.fulldomain) {
        throw "Le CNAME _acme-challenge.$HostName n'est pas encore visible (trouve : '$cible', attendu : '$($inscription.fulldomain)'). Attendez la propagation DNS puis relancez."
    }
    Trace "CNAME verifie : _acme-challenge.$HostName -> $cible" 'Green'

    if (-not (Get-PAAccount)) { New-PAAccount -AcceptTOS | Out-Null }
    $certificat = New-PACertificate $HostName -Plugin AcmeDns -PluginArgs $pluginArgs -AcceptTOS -Force
    Trace "Certificat emis par $($certificat.Issuer -replace '^.*?CN=([^,]+).*$','$1'), expire le $($certificat.NotAfter)" 'Green'
}

# Import dans le magasin WebHosting et liaison IIS sur le port 443.
$importe = Import-PfxCertificate -FilePath $certificat.PfxFullChain -CertStoreLocation 'Cert:\LocalMachine\WebHosting' -Password $certificat.PfxPass
Remove-Item 'IIS:\SslBindings\0.0.0.0!443' -ErrorAction SilentlyContinue
New-Item 'IIS:\SslBindings\0.0.0.0!443' -Thumbprint $importe.Thumbprint -SSLFlags 0 -Store WebHosting | Out-Null
Trace "IIS : port 443 lie au certificat $($importe.Thumbprint)" 'Green'

if (-not $Renouvellement) {
    $tache = 'NERE - Renouvellement certificat HTTPS'
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`" -Renouvellement$(if ($Test) { ' -Test' })"
    $declencheur = New-ScheduledTaskTrigger -Daily -At '03:30'
    Register-ScheduledTask -TaskName $tache -Action $action -Trigger $declencheur -User 'SYSTEM' -RunLevel Highest -Force | Out-Null
    Trace "Tache planifiee '$tache' : chaque jour a 03:30 (journal : $journal)" 'Green'
}
