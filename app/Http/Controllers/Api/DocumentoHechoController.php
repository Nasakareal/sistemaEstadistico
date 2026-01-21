<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hechos;

class DocumentoHechoController extends Controller
{
    public function descargarDoc(Request $request, $hecho)
    {
        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])->findOrFail($hecho);
        $html = view('hechos.reporte_docx', compact('hecho'))->render();
        $wordContent = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte del Hecho</title>
    <style>
        @page Section1 {
            size: 21.59cm 35.56cm; /* Oficio */
            margin: 1.0cm 1.0cm 1.0cm 1.0cm;
            mso-page-orientation: portrait;
        }
        div.Section1 { page: Section1; }
    </style>
</head>
<body>
    <div class="Section1">
        {$html}
    </div>
</body>
</html>
HTML;

        $filename = 'hecho_' . ($hecho->folio_c5i ?: $hecho->id) . '.doc';

        // ✅ Respuesta como descarga
        return response($wordContent)
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
