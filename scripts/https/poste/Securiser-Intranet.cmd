@echo off
rem Fait approuver le certificat HTTPS de l'intranet NERE Mining (https://192.168.10.206) par ce poste.
rem Copier ce fichier sur le poste et double-cliquer dessus (accepter la demande administrateur).
rem Le certificat public est integre ci-dessous : aucun autre fichier n'est necessaire.
setlocal
set "URL=https://192.168.10.206/"
set "EMPREINTE=C044B43D23054999374BAF109CBB1678AFC21EFF"
set "ANCIENNE=DDFE6E8545A4CA68510CB68D248E6D5679611831"
set "CER=%TEMP%\intranet-nere-mining-ip.cer"

net session >nul 2>&1
if errorlevel 1 (
    echo Demande des droits administrateur...
    powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo.
echo === Intranet NERE Mining : installation du certificat HTTPS ===
echo.

(
echo -----BEGIN CERTIFICATE-----
echo MIIDmDCCAoCgAwIBAgIQXohc5EN40L5OuEYe3H0BKTANBgkqhkiG9w0BAQsFADBCMREwDwYDVQQL
echo DAhJbnRyYW5ldDEUMBIGA1UECgwLTkVSRSBNaW5pbmcxFzAVBgNVBAMMDjE5Mi4xNjguMTAuMjA2
echo MB4XDTI2MTAyNzExMjgyM1oXDTI4MTAyNjExMzgyMlowQjERMA8GA1UECwwISW50cmFuZXQxFDAS
echo BgNVBAoMC05FUkUgTWluaW5nMRcwFQYDVQQDDA4xOTIuMTY4LjEwLjIwNjCCASIwDQYJKoZIhvcN
echo AQEBBQADggEPADCCAQoCggEBALcv/1ccIgEYSEGQncsBNrHCxP5reAfwfeBsXnwRSFjX1zKRmjJI
echo PRRGGojvfVb45BfSuJxTydh8ECPYIUR74VnG9flKZZcU5cPhTsK4Br888dQ9X5BaAYShEVerGyC7
echo o27/z3Vu0XMllET5Zj/v3TMXt+7/xZy65mWqvK8Ls/GQUOkQcLIznboUvGJ1L5IFm0v6ewU7SJat
echo kkrze6cKINsm5EoAzgkjOXJ2BOnveiMNUuoRUTh6cZA+arpwnPsHdCxrsdFJ8WSdzluaGddBZ06B
echo eOl+gnLsAW8mX8uf0JC7/opWZOsJjOF6DTJr3V6s3vA1FPkH6l7yyrSTYi2AiL8CAwEAAaOBiTCB
echo hjAOBgNVHQ8BAf8EBAMCBaAwQAYDVR0RBDkwN4cEwKgKzoIeT1VBLU5NLUlOVFJBTkVULm5lcmVt
echo aW5pbmcuY29tgg9PVUEtTk0tSU5UUkFORVQwEwYDVR0lBAwwCgYIKwYBBQUHAwEwHQYDVR0OBBYE
echo FJ32rhJfCTdn2H6+sMoqSlZww9ykMA0GCSqGSIb3DQEBCwUAA4IBAQA054TCpeYObVj/DeyEV8en
echo II9ymGe5t5wm8U+aFTMWoKLll9Gvo/TCaUbTExS+podj7wxYwNr/gXT3tZZ7PFWoEoAn/JRfgLEL
echo 09brlLKIWNgaSwPVxCUd+Wp94LYvLq+8gatmZmT6fDHr8oN5toZyNAUEV80fOx3tKk4V13rvggJw
echo wuJi3a5UOQ+msqoUCfD16Ux+u5cYXPwZOw8ZVtuqXZqJMKxR7f6fTXC0y9Yo8Acd3BBnncWNt7rs
echo EnycrQg0x8aFS+CC9+95zmyzJF/ESRta8wct5pTwdZkhCt0UOy6iKN1TLh3d/mQJJCTCXflZcNsa
echo Rhc9byBpDcr/WBcn
echo -----END CERTIFICATE-----
) > "%CER%"

rem Ancien certificat (nom OUA-NM-INTRANET), remplace le 27/10/2026.
certutil -delstore Root %ANCIENNE% >nul 2>&1

certutil -f -addstore Root "%CER%" >nul
if errorlevel 1 (
    echo [ECHEC] Le certificat n'a pas pu etre installe.
    del "%CER%" >nul 2>&1
    goto fin
)
del "%CER%" >nul 2>&1
certutil -store Root %EMPREINTE% >nul 2>&1
if errorlevel 1 (
    echo [ECHEC] Certificat introuvable apres installation.
    goto fin
)
echo [OK] Certificat approuve par Windows ^(Edge, Chrome^). Valable jusqu'au 26/10/2028.

rem Firefox a son propre magasin : on lui fait reprendre les certificats approuves par Windows.
if exist "%ProgramFiles%\Mozilla Firefox\firefox.exe" goto firefox
if exist "%ProgramFiles(x86)%\Mozilla Firefox\firefox.exe" goto firefox
goto test
:firefox
reg add "HKLM\SOFTWARE\Policies\Mozilla\Firefox\Certificates" /v ImportEnterpriseRoots /t REG_DWORD /d 1 /f >nul
echo [OK] Firefox configure pour utiliser ce certificat.

:test
echo.
echo Test de connexion a %URL% ...
for /f %%c in ('curl.exe -s -o NUL -w "%%{http_code}" --ssl-no-revoke --max-time 15 %URL% 2^>nul') do set "CODE=%%c"
if "%CODE%"=="200" (
    echo [OK] Connexion securisee a l'intranet.
) else (
    echo [ATTENTION] Pas de reponse securisee ^(code "%CODE%"^) : verifier le reseau vers 192.168.10.206.
)
echo.
echo Fermez puis rouvrez le navigateur, puis ouvrez %URL%

:fin
echo.
pause
