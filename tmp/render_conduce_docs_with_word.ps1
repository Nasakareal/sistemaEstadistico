$ErrorActionPreference = 'Stop'

$base = 'C:\wamp64\www\sistemaEstadistico\exports_conduce_2026-11-11'
$documents = Get-ChildItem -LiteralPath $base -Filter '*.docx' | Sort-Object Name
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0

try {
    foreach ($source in $documents) {
        $qaName = 'qa_' + $source.BaseName
        $qaDir = Join-Path $base $qaName
        New-Item -ItemType Directory -Path $qaDir -Force | Out-Null
        $pdfPath = Join-Path $qaDir ($source.BaseName + '.pdf')
        $doc = $word.Documents.Open($source.FullName, $false, $true)
        try {
            $doc.ExportAsFixedFormat($pdfPath, 17)
        }
        finally {
            $doc.Close($false)
        }
        Write-Output $pdfPath
    }
}
finally {
    $word.Quit()
    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
}
