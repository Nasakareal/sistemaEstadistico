<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;

class DocumentoHechoController extends Controller
{
    public function descargarDocx($id)
    {
        // Obtener el hecho con los vehículos, conductores y lesionados relacionados
        $hecho = Hechos::with(['vehiculos.conductores', 'lesionados'])->findOrFail($id);

        // Renderizar la vista con los datos
        $html = view('hechos.reporte_docx', compact('hecho'))->render();

        // Construir el contenido con el bloque HEREDOC
        $wordContent = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:w="urn:schemas-microsoft-com:office:word"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte del Hecho</title>
    <style>
        /* Sección para forzar tamaño Oficio */
        @page Section1 {
            size: 21.59cm 35.56cm; /* 8.5 x 14 pulgadas en cm */
            margin: 1.0cm 1.0cm 1.0cm 1.0cm;
            mso-page-orientation: portrait;
        }
        div.Section1 {
            page: Section1;
        }
    </style>
</head>
<body>
    <!-- Contenedor que aplica la sección -->
    <div class="Section1">
        {$html}
    </div>
</body>
</html>
HTML;

        // Retornar el contenido como archivo .doc
        return response($wordContent)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="hecho_' . $hecho->folio_c5i . '.doc"');
    }
}
