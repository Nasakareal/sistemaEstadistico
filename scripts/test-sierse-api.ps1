param(
    [Parameter(Mandatory = $true)]
    [string] $BaseUrl,

    [string] $ClientId = 'galvan',

    [Parameter(Mandatory = $true)]
    [string] $KeyId,

    [ValidateSet('inventory', 'plate', 'vin', 'conduce_folio')]
    [string] $SearchType = 'inventory',

    [Parameter(Mandatory = $true)]
    [string] $SearchValue,

    [string] $CertificatePfx
)

$ErrorActionPreference = 'Stop'

function Convert-SecureStringToPlainText {
    param([Security.SecureString] $Value)

    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($Value)
    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer)
    }
}

function Get-HexString {
    param([byte[]] $Bytes)
    return -join ($Bytes | ForEach-Object { $_.ToString('x2') })
}

$secretSecure = Read-Host 'Secreto HMAC de Galvan' -AsSecureString
$secret = Convert-SecureStringToPlainText $secretSecure

$encodedType = [Uri]::EscapeDataString($SearchType)
$encodedValue = [Uri]::EscapeDataString($SearchValue)
$requestUrl = $BaseUrl.TrimEnd('/') + "/buscar?type=$encodedType&value=$encodedValue"
$uri = [Uri] $requestUrl
$timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds().ToString()
$nonceBytes = New-Object byte[] 24
[Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($nonceBytes)
$nonce = Get-HexString $nonceBytes

$emptyBodyHash = [Security.Cryptography.SHA256]::Create().ComputeHash([Text.Encoding]::UTF8.GetBytes(''))
$canonical = @(
    'GET'
    $uri.PathAndQuery
    $ClientId
    $KeyId
    $timestamp
    $nonce
    (Get-HexString $emptyBodyHash)
) -join "`n"

$hmac = New-Object Security.Cryptography.HMACSHA256
$hmac.Key = [Text.Encoding]::UTF8.GetBytes($secret)
$signature = Get-HexString $hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($canonical))
$secret = $null

$headers = @{
    'X-SIERSE-Client' = $ClientId
    'X-SIERSE-Key-Id' = $KeyId
    'X-SIERSE-Timestamp' = $timestamp
    'X-SIERSE-Nonce' = $nonce
    'X-SIERSE-Signature' = $signature
    'Accept' = 'application/json'
}

$requestParameters = @{
    Uri = $requestUrl
    Method = 'GET'
    Headers = $headers
}

if ($CertificatePfx) {
    $certificatePassword = Read-Host 'Contrasena del certificado PFX' -AsSecureString
    $certificatePasswordPlain = Convert-SecureStringToPlainText $certificatePassword
    try {
        $certificate = New-Object Security.Cryptography.X509Certificates.X509Certificate2(
            $CertificatePfx,
            $certificatePasswordPlain
        )
        $requestParameters.Certificate = $certificate
    }
    finally {
        $certificatePasswordPlain = $null
    }
}

Write-Host "Consultando $requestUrl" -ForegroundColor Cyan
$response = Invoke-RestMethod @requestParameters
$response | ConvertTo-Json -Depth 12
