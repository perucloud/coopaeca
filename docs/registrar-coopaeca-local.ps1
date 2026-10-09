# Ejecutar una sola vez desde PowerShell como administrador.
$ErrorActionPreference = 'Stop'
$hostsFile = "$env:WINDIR\System32\drivers\etc\hosts"
if (-not (Select-String -LiteralPath $hostsFile -Pattern '\bcoopaeca\.test\b' -Quiet)) {
    Copy-Item -LiteralPath $hostsFile -Destination "$hostsFile.coopaeca-backup"
    Add-Content -LiteralPath $hostsFile -Value "`r`n127.0.0.1 coopaeca.test" -Encoding ascii
}
ipconfig /flushdns
Write-Host 'Dominio listo: http://coopaeca.test'