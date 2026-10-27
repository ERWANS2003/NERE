<#
.SYNOPSIS
    Fait approuver le certificat de l'intranet par tous les PC du domaine neremining.com (GPO).

.DESCRIPTION
    A lancer par un ADMINISTRATEUR DU DOMAINE, sur un controleur de domaine ou un poste
    disposant de la console Gestion des strategies de groupe (RSAT).
      1. Cree (ou reutilise) la GPO "NERE - Certificat intranet HTTPS".
      2. Y place le certificat public dans "Autorites de certification racines de confiance"
         (Configuration ordinateur), exactement comme un import manuel dans la console GPMC.
      3. Lie la GPO a la racine du domaine, ou a l'OU passee en parametre.

    Les postes appliquent la GPO au prochain rafraichissement (90 min environ) ou apres
    "gpupdate /force". Seul le certificat public (.cer) est utilise, jamais le .pfx.

.EXAMPLE
    .\Deployer-CertificatGPO.ps1 -CerPath .\certificats\intranet-nere-mining.cer

.EXAMPLE
    .\Deployer-CertificatGPO.ps1 -CerPath .\certificats\intranet-nere-mining.cer -Cible "OU=Postes,DC=neremining,DC=com"
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$CerPath,

    [string]$NomGpo = 'NERE - Certificat intranet HTTPS',

    # Distinguished name de l'OU cible. Par defaut : racine du domaine (tous les ordinateurs).
    [string]$Cible
)

$ErrorActionPreference = 'Stop'

foreach ($module in 'GroupPolicy', 'ActiveDirectory') {
    if (-not (Get-Module -ListAvailable -Name $module)) {
        throw "Module '$module' absent : lancez ce script sur un controleur de domaine ou installez les outils RSAT."
    }
}
Import-Module GroupPolicy
Import-Module ActiveDirectory

if ([IO.Path]::GetExtension($CerPath) -ne '.cer') {
    throw 'Utilisez le certificat public .cer, jamais le .pfx.'
}
$certificat = New-Object Security.Cryptography.X509Certificates.X509Certificate2((Resolve-Path -LiteralPath $CerPath).Path)
if ($certificat.HasPrivateKey) { throw 'Ce fichier contient une cle privee : il ne doit pas etre diffuse.' }
Write-Host "Certificat : $($certificat.Subject) | empreinte $($certificat.Thumbprint) | expire $($certificat.NotAfter.ToString('dd/MM/yyyy'))" -ForegroundColor Cyan

# La GPO stocke le certificat sous forme de "blob" Windows (le meme format que le magasin
# de certificats). On le produit avec un magasin temporaire en memoire, sans toucher
# aux magasins de cette machine.
try {
    $store = New-Object Security.Cryptography.X509Certificates.X509Store('NereTempStore', 'CurrentUser')
    $store.Open('ReadWrite')
    $store.Add($certificat)
    $store.Close()
    $blob = (Get-ItemProperty "HKCU:\Software\Microsoft\SystemCertificates\NereTempStore\Certificates\$($certificat.Thumbprint)").Blob
} finally {
    Remove-Item 'HKCU:\Software\Microsoft\SystemCertificates\NereTempStore' -Recurse -Force -ErrorAction SilentlyContinue
}
if (-not $blob) { throw 'Impossible de preparer le certificat pour la GPO.' }

$gpo = Get-GPO -Name $NomGpo -ErrorAction SilentlyContinue
if (-not $gpo) {
    $gpo = New-GPO -Name $NomGpo -Comment "Approuve le certificat HTTPS de l'intranet NERE ($($certificat.Subject))."
    Write-Host "GPO creee : $NomGpo" -ForegroundColor Green
} else {
    Write-Host "GPO existante reutilisee : $NomGpo" -ForegroundColor Green
}

Set-GPRegistryValue -Name $NomGpo `
    -Key "HKLM\SOFTWARE\Policies\Microsoft\SystemCertificates\Root\Certificates\$($certificat.Thumbprint)" `
    -ValueName 'Blob' -Type Binary -Value $blob | Out-Null
Write-Host 'Certificat ajoute aux autorites racines de confiance de la GPO.' -ForegroundColor Green

if (-not $Cible) { $Cible = (Get-ADDomain).DistinguishedName }
$dejaLiee = (Get-GPInheritance -Target $Cible).GpoLinks | Where-Object { $_.DisplayName -eq $NomGpo }
if (-not $dejaLiee) {
    New-GPLink -Name $NomGpo -Target $Cible -LinkEnabled Yes | Out-Null
    Write-Host "GPO liee a : $Cible" -ForegroundColor Green
} else {
    Write-Host "GPO deja liee a : $Cible" -ForegroundColor Green
}

Write-Host "`nSur un poste, pour appliquer tout de suite : gpupdate /force puis redemarrer le navigateur." -ForegroundColor Yellow
Write-Host "Verification sur un poste :" -ForegroundColor Yellow
Write-Host "    Get-ChildItem Cert:\LocalMachine\Root | Where-Object Thumbprint -eq '$($certificat.Thumbprint)'"
