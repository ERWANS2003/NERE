<#
.SYNOPSIS
    Fait approuver le certificat auto-signe de l'intranet par un poste client.

.DESCRIPTION
    Le certificat de l'intranet est auto-signe : sans cette etape, Edge et Chrome affichent
    "Votre connexion n'est pas privee". A lancer en administrateur sur chaque poste, ou mieux,
    a deployer une seule fois pour tout le domaine par strategie de groupe (voir docs/HTTPS_IIS.md).

    Seule la partie publique (.cer, exportee par Activer-HTTPS.ps1) est utilisee :
    ne diffusez jamais le .pfx, qui contient la cle privee.

.EXAMPLE
    .\Installer-CertificatClient.ps1 -CerPath "\\OUA-NM-INTRANET\partage\intranet-nere-mining.cer"
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$CerPath
)

$ErrorActionPreference = 'Stop'

$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}
if ([IO.Path]::GetExtension($CerPath) -ne '.cer') {
    throw 'Utilisez le fichier .cer (certificat public), jamais le .pfx.'
}

$certificat = Import-Certificate -FilePath $CerPath -CertStoreLocation 'Cert:\LocalMachine\Root'
Write-Host "Certificat approuve sur ce poste : $($certificat.Subject)" -ForegroundColor Green
Write-Host "Valable jusqu'au $($certificat.NotAfter.ToString('dd/MM/yyyy')). Redemarrez le navigateur." -ForegroundColor Green
