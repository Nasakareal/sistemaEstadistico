$ErrorActionPreference = 'Stop'
$source = 'C:\wamp64\www\sistemaEstadistico\exports_conduce_2026-11-11\TOTALES_CONDUCE_LEGALIDAD_2026-11-11_01_CALZADA_LA_HUERTA.docx'
$pdf = 'C:\wamp64\www\sistemaEstadistico\exports_conduce_2026-11-11\qa_TOTALES_CONDUCE_LEGALIDAD_2026-11-11_01_CALZADA_LA_HUERTA\test.pdf'
Write-Output 'starting-word'
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    Write-Output 'opening-document'
    $doc = $word.Documents.Open($source, $false, $true, $false)
    try {
        Write-Output ('opened-pages=' + $doc.ComputeStatistics(2))
        $doc.SaveAs2($pdf, 17)
        Write-Output ('saved=' + (Test-Path -LiteralPath $pdf))
    }
    finally {
        $doc.Close($false)
    }
}
finally {
    $word.Quit()
    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
}
