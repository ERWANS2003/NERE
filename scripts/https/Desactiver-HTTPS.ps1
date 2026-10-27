<#
.SYNOPSIS
    Annule Activer-HTTPS.ps1 : retire la redirection et la liaison HTTPS, restaure le .env.

.DESCRIPTION
    A lancer sur le serveur en administrateur, en cas de probleme apres l'activation du HTTPS.
    Le certificat reste installe dans LocalMachine\My (sans effet tant qu'aucune liaison ne l'utilise).

.EXAMPLE
    .\Desactiver-HTTPS.ps1 -EnvBackup "E:\web\itsm-nere-mining\.env.avant-https-20261027-101500"
#>
[CmdletBinding()]
param(
    # Par defaut : le site IIS dont le dossier contient "itsm-nere-mining".
    [string]$SiteName,

    [string]$HostName = 'OUA-NM-INTRANET.neremining.com',

    [int]$Port = 443,

    # Sauvegarde du .env creee par Activer-HTTPS.ps1 (affichee a la fin de son execution).
    [string]$EnvBackup
)

$ErrorActionPreference = 'Stop'

$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}
Import-Module WebAdministration
if (-not $SiteName) {
    $SiteName = @(Get-Website | Where-Object { $_.physicalPath -like '*itsm-nere-mining*' })[0].Name
}

Write-Host '==> Suppression de la redirection HTTP vers HTTPS' -ForegroundColor Cyan
Clear-WebConfiguration -PSPath 'MACHINE/WEBROOT/APPHOST' -Location $SiteName `
    -Filter "system.webServer/rewrite/rules/rule[@name='NERE - Forcer HTTPS']" -ErrorAction SilentlyContinue

Write-Host "==> Suppression de la liaison https ${HostName}:$Port" -ForegroundColor Cyan
Remove-WebBinding -Name $SiteName -Protocol https -Port $Port -HostHeader $HostName -ErrorAction SilentlyContinue

if ($EnvBackup) {
    if (-not (Test-Path -LiteralPath $EnvBackup)) { throw "Sauvegarde introuvable : $EnvBackup" }
    $fichierEnv = Join-Path (Split-Path $EnvBackup -Parent) '.env'
    Write-Host "==> Restauration de $fichierEnv" -ForegroundColor Cyan
    Copy-Item -LiteralPath $EnvBackup -Destination $fichierEnv -Force

    $php = (Get-Command php -ErrorAction SilentlyContinue).Source
    if ($php) {
        Push-Location (Split-Path $fichierEnv -Parent)
        try { & $php artisan config:cache | Out-Null } finally { Pop-Location }
    } else {
        Write-Host "    !!  Lancez 'php artisan config:cache' dans le dossier de l'application." -ForegroundColor Yellow
    }
}

Stop-Website -Name $SiteName
Start-Website -Name $SiteName
Write-Host "`nHTTPS desactive, le site '$SiteName' repond de nouveau en HTTP." -ForegroundColor Green
