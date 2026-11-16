param(
    [string] $DeliveryDirectory = 'C:\Users\naasa\Desktop\ENTREGA_GALVAN_API'
)

$ErrorActionPreference = 'Stop'

function Read-LabeledValue([string] $Path, [string] $Label) {
    $line = Get-Content -LiteralPath $Path | Where-Object { $_ -like "$Label*" } | Select-Object -First 1
    if (-not $line) { throw "No se encontro $Label en $Path" }
    return ($line.Substring($line.IndexOf(':') + 1)).Trim()
}

function Get-Hex([byte[]] $Bytes) {
    return -join ($Bytes | ForEach-Object { $_.ToString('x2') })
}

function New-Nonce {
    $bytes = New-Object byte[] 24
    [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
    return Get-Hex $bytes
}

$apiFile = Join-Path $DeliveryDirectory 'api-galvan.txt'
$certFile = Join-Path $DeliveryDirectory 'certificado-galvan.txt'
$baseUrl = Read-LabeledValue $apiFile 'URL base:'
$clientId = Read-LabeledValue $apiFile 'Client ID:'
$keyId = Read-LabeledValue $apiFile 'Key ID:'
$secret = Read-LabeledValue $apiFile 'Secreto HMAC:'
$pfxName = Read-LabeledValue $certFile 'Archivo PFX:'
$pfxPassword = Read-LabeledValue $certFile 'Contraseña PFX:'
$certificate = New-Object Security.Cryptography.X509Certificates.X509Certificate2(
    (Join-Path $DeliveryDirectory $pfxName),
    $pfxPassword
)

function Invoke-Sierse([string] $Method, [string] $RelativeUrl, [string] $Body = '') {
    $url = if ($RelativeUrl.StartsWith('/api/')) {
        ([Uri] $baseUrl).GetLeftPart([UriPartial]::Authority) + $RelativeUrl
    } else {
        $baseUrl.TrimEnd('/') + $RelativeUrl
    }
    $uri = [Uri] $url
    $timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds().ToString()
    $nonce = New-Nonce
    $bodyHash = Get-Hex ([Security.Cryptography.SHA256]::Create().ComputeHash([Text.Encoding]::UTF8.GetBytes($Body)))
    $canonical = @($Method, $uri.PathAndQuery, $clientId, $keyId, $timestamp, $nonce, $bodyHash) -join "`n"
    $hmac = New-Object Security.Cryptography.HMACSHA256
    $hmac.Key = [Text.Encoding]::UTF8.GetBytes($secret)
    $signature = Get-Hex $hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes($canonical))
    $headers = @{
        'X-SIERSE-Client' = $clientId
        'X-SIERSE-Key-Id' = $keyId
        'X-SIERSE-Timestamp' = $timestamp
        'X-SIERSE-Nonce' = $nonce
        'X-SIERSE-Signature' = $signature
        'Accept' = 'application/json'
    }
    $parameters = @{
        Uri = $url
        Method = $Method
        Headers = $headers
        Certificate = $certificate
    }
    if ($Body -ne '') {
        $parameters.ContentType = 'application/json'
        $parameters.Body = $Body
    }
    return Invoke-RestMethod @parameters
}

$status = Invoke-Sierse 'GET' '/estado'
$list = Invoke-Sierse 'GET' '/vehiculos?source=vehicle_registry&per_page=1&page=1'
$vehicle = @($list.data)[0]
if (-not $vehicle.id) { throw 'No hay un vehiculo Galvan disponible para la prueba inocua.' }
$conduceList = Invoke-Sierse 'GET' '/vehiculos?source=conduce_legalidad&per_page=100&page=1'
$conduceVehicle = @($conduceList.data | Where-Object { @($_.photos).Count -gt 0 } | Select-Object -First 1)[0]
$vehiclePhoto = @($vehicle.photos | Select-Object -First 1)[0]
$vehiclePhotoBytes = if ($vehiclePhoto.download_path) {
    @(Invoke-Sierse 'GET' $vehiclePhoto.download_path).Count
} else { 0 }
$conducePhoto = @($conduceVehicle.photos | Select-Object -First 1)[0]
$conducePhotoBytes = if ($conducePhoto.download_path) {
    @(Invoke-Sierse 'GET' $conducePhoto.download_path).Count
} else { 0 }

$eventId = 'DEPLOY-SMOKE-' + [DateTimeOffset]::UtcNow.ToString('yyyyMMddHHmmssfff')
$bodyObject = [ordered]@{
    external_event_id = $eventId
    event_type = 'other'
    schema_version = '1.0'
    data = [ordered]@{ purpose = 'deployment_smoke_test' }
}
$body = $bodyObject | ConvertTo-Json -Depth 5 -Compress
$created = Invoke-Sierse 'POST' "/vehiculos/vehicle_registry/$($vehicle.id)/eventos" $body
$queried = Invoke-Sierse 'GET' "/eventos/$eventId"

[pscustomobject]@{
    status_ok = [bool] $status.ok
    vehicle_read_ok = [bool] $vehicle.id
    vehicle_id = [int] $vehicle.id
    integration_state_visible = ($null -ne $vehicle.sierse_integration)
    vehicle_photo_count = @($vehicle.photos).Count
    vehicle_photo_types = @($vehicle.photos | ForEach-Object { $_.type } | Select-Object -Unique)
    vehicle_photo_download_ok = ($vehiclePhotoBytes -gt 0)
    conduce_read_ok = [bool] $conduceVehicle.id
    conduce_photo_count = @($conduceVehicle.photos).Count
    conduce_photo_types = @($conduceVehicle.photos | ForEach-Object { $_.type } | Select-Object -Unique)
    conduce_photo_download_ok = ($conducePhotoBytes -gt 0)
    event_status = $created.event.status
    processing_message = $created.event.processing_message
    event_query_status = $queried.event.status
} | ConvertTo-Json -Depth 4

$secret = $null
$pfxPassword = $null
