<#
.SYNOPSIS
    Compile l'installateur graphique Installer-Intranet-NERE.exe avec le
    compilateur C# fourni par Windows (.NET Framework 4), sans rien telecharger.

.EXAMPLE
    .\scripts\https\poste\installateur\Construire-Installateur.ps1
#>
param(
    [string]$Sortie = (Join-Path $PSScriptRoot '..\Installer-Intranet-NERE.exe')
)

$ErrorActionPreference = 'Stop'
$csc = Join-Path $env:windir 'Microsoft.NET\Framework64\v4.0.30319\csc.exe'
if (-not (Test-Path $csc)) { throw "Compilateur introuvable : $csc" }

$racine = Resolve-Path (Join-Path $PSScriptRoot '..\..\..\..')
$icone = Join-Path $racine 'public\favicon.ico'

& $csc /nologo /target:winexe /optimize+ /codepage:65001 `
    "/out:$Sortie" `
    "/win32icon:$icone" `
    "/win32manifest:$(Join-Path $PSScriptRoot 'app.manifest')" `
    "/resource:$icone,intranet.ico" `
    "/resource:$(Join-Path $racine 'public\images\pwa-icon-192.png'),logo.png" `
    /reference:System.Windows.Forms.dll /reference:System.Drawing.dll `
    (Join-Path $PSScriptRoot 'InstallateurIntranet.cs')
if ($LASTEXITCODE -ne 0) { throw "La compilation a echoue (code $LASTEXITCODE)." }

$fichier = Get-Item $Sortie
"Installateur : $($fichier.FullName) ($([math]::Round($fichier.Length / 1KB)) Ko)"
"SHA-256 : $((Get-FileHash $fichier.FullName -Algorithm SHA256).Hash)"
