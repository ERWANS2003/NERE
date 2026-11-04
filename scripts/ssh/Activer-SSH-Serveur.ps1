<#
.SYNOPSIS
    Active OpenSSH Server sur OUA-NM-INTRANET et autorise une seule cle publique.

.DESCRIPTION
    A lancer UNE FOIS sur le serveur, dans PowerShell "Executer en tant qu'administrateur"
    (ouvrir une session Bureau a distance sur 192.168.10.206 pour le copier et le lancer).
      1. Installe la fonctionnalite Windows OpenSSH Server (si absente).
      2. Demarre le service sshd et le passe en demarrage automatique.
      3. Ouvre le port 22 uniquement pour l'adresse du poste autorise.
      4. Desactive la connexion par mot de passe : seule la cle ci-dessous est acceptee.
      5. Ajoute la cle dans administrators_authorized_keys (droits Administrateurs et SYSTEM).
      6. Choisit PowerShell comme shell par defaut.

    Retour arriere : .\Activer-SSH-Serveur.ps1 -Desactiver

.EXAMPLE
    .\Activer-SSH-Serveur.ps1
#>
[CmdletBinding()]
param(
    [string]$PublicKey = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIE/T2PelrlvN1GI6AALd4raDq4GWVqqINeF/xNJE1crM claude-code@ERWAN-LAPTOP vers OUA-NM-INTRANET',

    # Seul ce poste pourra joindre le port 22.
    [string]$PosteAutorise = '192.168.10.27',

    # Installation hors ligne (serveur sans Windows Update) depuis le MSI officiel.
    [string]$MsiPath,

    [string]$MsiSha256 = 'DDEC9C53864280759CF9F74791CEFD387100E3946AA849A1C138A4ED1B96B7D9',

    [switch]$Desactiver
)

$ErrorActionPreference = 'Stop'
$regle = 'Intranet NERE - SSH administration'
$cles = "$env:ProgramData\ssh\administrators_authorized_keys"
$config = "$env:ProgramData\ssh\sshd_config"

$identite = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identite.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Lancez ce script dans une console PowerShell ouverte en tant qu''administrateur.'
}

if ($Desactiver) {
    Stop-Service sshd -ErrorAction SilentlyContinue
    Set-Service sshd -StartupType Disabled -ErrorAction SilentlyContinue
    Remove-NetFirewallRule -DisplayName $regle -ErrorAction SilentlyContinue
    if (Test-Path $cles) {
        (Get-Content $cles) | Where-Object { $_ -ne $PublicKey } | Set-Content -Path $cles -Encoding ascii
    }
    Write-Host 'SSH desactive et cle retiree.' -ForegroundColor Green
    return
}

Write-Host '==> Installation d''OpenSSH Server' -ForegroundColor Cyan
if (-not (Get-Service sshd -ErrorAction SilentlyContinue)) {
    if ($MsiPath) {
        # Paquet officiel PowerShell/Win32-OpenSSH : fonctionne sans Windows Update.
        if (-not (Test-Path -LiteralPath $MsiPath)) { throw "MSI introuvable : $MsiPath" }
        # msiexec ne resout pas les chemins relatifs (erreur 1619) : on lui passe le chemin complet.
        $MsiPath = (Resolve-Path -LiteralPath $MsiPath).Path
        Unblock-File -LiteralPath $MsiPath
        $empreinte = (Get-FileHash -LiteralPath $MsiPath -Algorithm SHA256).Hash
        if ($MsiSha256 -and $empreinte -ne $MsiSha256.ToUpper()) {
            throw "Empreinte SHA256 inattendue pour le MSI ($empreinte) : fichier altere, installation annulee."
        }
        $installation = Start-Process msiexec.exe -ArgumentList "/i `"$MsiPath`" ADDLOCAL=Server /qn /norestart" -Wait -PassThru
        if ($installation.ExitCode -ne 0) { throw "msiexec a echoue (code $($installation.ExitCode))." }
    } else {
        try {
            Add-WindowsCapability -Online -Name (Get-WindowsCapability -Online -Name 'OpenSSH.Server*').Name | Out-Null
        } catch {
            throw "Windows Update injoignable. Relancez avec -MsiPath <chemin de OpenSSH-Win64-v10.0.0.0.msi>."
        }
    }
}
Start-Service sshd
Set-Service sshd -StartupType Automatic
Write-Host '    OK  service sshd demarre (demarrage automatique)' -ForegroundColor Green

Write-Host "==> Pare-feu : port 22 ouvert uniquement pour $PosteAutorise" -ForegroundColor Cyan
Get-NetFirewallRule -Name 'OpenSSH-Server-In-TCP' -ErrorAction SilentlyContinue | Disable-NetFirewallRule
Remove-NetFirewallRule -DisplayName $regle -ErrorAction SilentlyContinue
New-NetFirewallRule -DisplayName $regle -Direction Inbound -Protocol TCP -LocalPort 22 -RemoteAddress $PosteAutorise -Action Allow | Out-Null
Write-Host '    OK' -ForegroundColor Green

Write-Host '==> Authentification par cle uniquement' -ForegroundColor Cyan
$lignes = Get-Content $config
$lignes = $lignes | Where-Object { $_ -notmatch '^\s*#?\s*(PasswordAuthentication|PubkeyAuthentication)\s' }
$lignes = @('PubkeyAuthentication yes', 'PasswordAuthentication no') + $lignes
Set-Content -Path $config -Value $lignes -Encoding ascii

if (-not (Test-Path $cles) -or -not (Select-String -Path $cles -SimpleMatch $PublicKey -Quiet)) {
    Add-Content -Path $cles -Value $PublicKey -Encoding ascii
}
# sshd refuse ce fichier si d'autres comptes que Administrateurs et SYSTEM peuvent le lire.
icacls $cles /inheritance:r /grant '*S-1-5-32-544:F' /grant '*S-1-5-18:F' | Out-Null
Write-Host "    OK  cle ajoutee a $cles" -ForegroundColor Green

Write-Host '==> PowerShell comme shell par defaut' -ForegroundColor Cyan
New-ItemProperty -Path 'HKLM:\SOFTWARE\OpenSSH' -Name DefaultShell -Value "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -PropertyType String -Force | Out-Null

Restart-Service sshd
Write-Host "`nTermine. Compte a utiliser pour la connexion : $env:USERNAME (doit etre administrateur)." -ForegroundColor Green
Write-Host "Depuis le poste $PosteAutorise : ssh -i <cle> $env:USERNAME@192.168.10.206" -ForegroundColor Green
