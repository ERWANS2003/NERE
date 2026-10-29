<#
.SYNOPSIS
    Sauvegarde nocturne de l'intranet : base PostgreSQL, fichiers deposes
    (storage\app) et .env, dans une archive zip datee.

.DESCRIPTION
    Lance chaque nuit par la tache planifiee "Backup ITSM Nere Mining" (compte SYSTEM).
    - pg_dump au format custom (restaurable avec pg_restore), mot de passe lu
      dans le .env et passe par PGPASSWORD (jamais sur la ligne de commande) ;
    - archive intranet_AAAAMMJJ_HHmmss.zip dans -Destination ;
    - suppression des archives intranet_*.zip de plus de -Jours jours ;
    - dossier de destination reserve aux administrateurs et a SYSTEM (le .env
      contient des secrets) ;
    - etat ecrit dans storage\app\sauvegarde-statut.json, affiche sur la page
      "Sante du serveur" de l'admin.
    Restauration : voir docs/SAUVEGARDE.md.
#>
param(
    [string]$App = 'C:\inetpub\wwwroot\itsm-nere-mining',
    [string]$Destination = 'C:\Backups\ITSM',
    [int]$Jours = 14
)

$ErrorActionPreference = 'Stop'
$statut = Join-Path $App 'storage\app\sauvegarde-statut.json'
$horodatage = Get-Date -Format 'yyyyMMdd_HHmmss'
$travail = Join-Path $env:TEMP "intranet-sauvegarde-$horodatage"

function Ecrire-Statut([hashtable]$Etat) {
    $Etat.date = (Get-Date).ToString('o')
    [IO.File]::WriteAllText($statut, ($Etat | ConvertTo-Json -Compress), (New-Object Text.UTF8Encoding($false)))
}

try {
    $config = @{}
    foreach ($ligne in [IO.File]::ReadAllLines((Join-Path $App '.env'))) {
        if ($ligne -match '^\s*(DB_[A-Z_]+)\s*=\s*"?(.*?)"?\s*$') { $config[$Matches[1]] = $Matches[2] }
    }

    $pgDump = Get-ChildItem 'C:\Program Files\PostgreSQL\*\bin\pg_dump.exe' |
        Sort-Object { [int]$_.Directory.Parent.Name } -Descending | Select-Object -First 1
    if (-not $pgDump) { throw 'pg_dump.exe introuvable dans C:\Program Files\PostgreSQL.' }

    if (-not (Test-Path $Destination)) { New-Item -ItemType Directory -Path $Destination | Out-Null }
    & icacls.exe $Destination /inheritance:r /grant:r '*S-1-5-32-544:(OI)(CI)F' '*S-1-5-18:(OI)(CI)F' | Out-Null

    New-Item -ItemType Directory -Path $travail | Out-Null
    $env:PGPASSWORD = $config['DB_PASSWORD']
    & $pgDump.FullName -h $config['DB_HOST'] -p $config['DB_PORT'] -U $config['DB_USERNAME'] -F c -f (Join-Path $travail 'base.dump') $config['DB_DATABASE']
    if ($LASTEXITCODE -ne 0) { throw "pg_dump a echoue (code $LASTEXITCODE)." }
    Remove-Item Env:\PGPASSWORD

    Copy-Item (Join-Path $App '.env') (Join-Path $travail 'env.txt')
    $fichiers = Join-Path $App 'storage\app'
    Copy-Item $fichiers (Join-Path $travail 'storage-app') -Recurse
    Remove-Item (Join-Path $travail 'storage-app\sauvegarde-statut.json') -ErrorAction SilentlyContinue

    $archive = Join-Path $Destination "intranet_$horodatage.zip"
    Compress-Archive -Path (Join-Path $travail '*') -DestinationPath $archive

    Get-ChildItem $Destination -Filter 'intranet_*.zip' |
        Where-Object LastWriteTime -lt (Get-Date).AddDays(-$Jours) |
        Remove-Item

    Ecrire-Statut @{
        ok = $true
        fichier = $archive
        taille_mo = [math]::Round((Get-Item $archive).Length / 1MB, 1)
        nombre = @(Get-ChildItem $Destination -Filter 'intranet_*.zip').Count
    }
    "Sauvegarde OK : $archive"
}
catch {
    Ecrire-Statut @{ ok = $false; erreur = $_.Exception.Message }
    Write-Error $_.Exception.Message
    exit 1
}
finally {
    Remove-Item Env:\PGPASSWORD -ErrorAction SilentlyContinue
    if (Test-Path $travail) { Remove-Item $travail -Recurse -Force }
}
