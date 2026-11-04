<#
.SYNOPSIS
    Etape 1/2 du certificat Let's Encrypt : prepare la validation DNS deleguee (acme-dns).

.DESCRIPTION
    A lancer une fois, sur le serveur, en administrateur.
      1. Installe le module PowerShell Posh-ACME (licence MIT) pour tous les utilisateurs.
      2. Cree une inscription sur le serveur acme-dns (auth.acme-dns.io par defaut).
         Elle ne peut publier que des enregistrements TXT de validation pour ce nom.
      3. Enregistre l'inscription dans C:\ProgramData\NereAcme (lisible par les seuls
         administrateurs et SYSTEM, compte de la tache de renouvellement).
      4. Affiche l'enregistrement CNAME a creer UNE FOIS dans la zone DNS publique
         neremining.com (NameBright).

    Ensuite : creer le CNAME, puis lancer LetsEncrypt-Emettre.ps1.

.EXAMPLE
    .\LetsEncrypt-Preparer.ps1
#>
[CmdletBinding()]
param(
    [string]$HostName = 'OUA-NM-INTRANET.neremining.com',

    [string]$AcmeDnsServer = 'auth.acme-dns.io'
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$dossier = 'C:\ProgramData\NereAcme'
$fichierInscription = Join-Path $dossier 'acme-dns.json'

$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

Write-Host '==> Module Posh-ACME' -ForegroundColor Cyan
if (-not (Get-Module -ListAvailable -Name Posh-ACME)) {
    if (-not (Get-PackageProvider -ListAvailable -Name NuGet -ErrorAction SilentlyContinue)) {
        Install-PackageProvider -Name NuGet -MinimumVersion 2.8.5.201 -Force -Scope AllUsers | Out-Null
    }
    Install-Module -Name Posh-ACME -Scope AllUsers -Force -AllowClobber
}
Write-Host "    OK  Posh-ACME $((Get-Module -ListAvailable Posh-ACME | Sort-Object Version -Descending | Select-Object -First 1).Version)" -ForegroundColor Green

Write-Host '==> Dossier de configuration protege' -ForegroundColor Cyan
New-Item -ItemType Directory -Force $dossier | Out-Null
# Administrateurs et SYSTEM uniquement : le fichier contient le secret acme-dns.
icacls $dossier /inheritance:r /grant '*S-1-5-32-544:(OI)(CI)F' /grant '*S-1-5-18:(OI)(CI)F' | Out-Null
Write-Host "    OK  $dossier" -ForegroundColor Green

Write-Host "==> Inscription acme-dns sur $AcmeDnsServer" -ForegroundColor Cyan
$nomDefi = "_acme-challenge.$HostName"
if (Test-Path $fichierInscription) {
    $inscription = Get-Content $fichierInscription -Raw | ConvertFrom-Json
    Write-Host '    OK  inscription existante reutilisee' -ForegroundColor Green
} else {
    $reponse = Invoke-RestMethod -Method Post -Uri "https://$AcmeDnsServer/register" -UseBasicParsing
    $inscription = [pscustomobject]@{
        server = $AcmeDnsServer
        challenge = $nomDefi
        subdomain = $reponse.subdomain
        username = $reponse.username
        password = $reponse.password
        fulldomain = $reponse.fulldomain
    }
    $inscription | ConvertTo-Json | Set-Content -Path $fichierInscription -Encoding ascii
    Write-Host '    OK  inscription creee et enregistree' -ForegroundColor Green
}

Write-Host "`nA FAIRE UNE SEULE FOIS chez NameBright (zone neremining.com) :" -ForegroundColor Yellow
Write-Host "    Type  : CNAME"
Write-Host "    Nom   : _acme-challenge.$($HostName.Split('.')[0])   (soit $nomDefi)"
Write-Host "    Cible : $($inscription.fulldomain)"
Write-Host "`nPuis lancez : .\LetsEncrypt-Emettre.ps1" -ForegroundColor Yellow
