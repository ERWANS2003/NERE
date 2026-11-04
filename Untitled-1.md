New-Item -ItemType Directory -Force C:\Certificats

$cert = New-SelfSignedCertificate `
  -DnsName "localhost" `
  -CertStoreLocation "Cert:\LocalMachine\My" `
  -FriendlyName "Intranet NERE HTTPS" `
  -Type SSLServerAuthentication `
  -KeyAlgorithm RSA `
  -KeyLength 3072 `
  -HashAlgorithm SHA256 `
  -KeyExportPolicy Exportable `
  -NotAfter (Get-Date).AddYears(2)

$motDePasse = Read-Host "Mot de passe du fichier PFX" -AsSecureString

Export-PfxCertificate `
  -Cert $cert `
  -FilePath C:\Certificats\intranet-neremining.pfx `
  -Password $motDePasse

Export-Certificate `
  -Cert $cert `
  -FilePath C:\Certificats\intranet-neremining.cer