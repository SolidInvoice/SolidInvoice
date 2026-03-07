# PLACEHOLDER: This install script requires Windows binary support.
# See https://github.com/SolidInvoice/SolidInvoice/issues for tracking.

$ErrorActionPreference = 'Stop'

$packageArgs = @{
    packageName    = 'solidinvoice'
    url64          = "https://github.com/SolidInvoice/SolidInvoice/releases/download/${env:chocolateyPackageVersion}/solidinvoice-windows-amd64.exe"
    fileFullPath   = "$(Get-ToolsLocation)\solidinvoice.exe"
    checksum64     = '' # Updated by CI
    checksumType64 = 'sha256'
}

Get-ChocolateyWebFile @packageArgs

# Add to PATH
$toolsDir = Get-ToolsLocation
Install-ChocolateyPath -PathToInstall $toolsDir -PathType 'Machine'

Write-Output "SolidInvoice installed. Run 'solidinvoice run' to start."
