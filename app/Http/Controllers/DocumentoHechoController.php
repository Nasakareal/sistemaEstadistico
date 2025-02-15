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

        // Renderizar la vista con los datos del hecho, vehículos, conductores y lesionados
        $html = view('hechos.reporte_docx', compact('hecho'))->render();

        // Envolver el contenido en un formato compatible con Word
        $wordContent = "
        <html xmlns:o='urn:schemas-microsoft-com:office:office'
              xmlns:w='urn:schemas-microsoft-com:office:word'
              xmlns='http://www.w3.org/TR/REC-html40'>
        <head>
            <meta charset='UTF-8'>
            <title>Reporte del Hecho</title>
        </head>
        <body>
            {$html}
        </body>
        </html>";

        // Configurar la respuesta para descargar el archivo con extensión `.doc`
        return response($wordContent)
            ->header('Content-Type', 'application/msword')
            ->header('Content-Disposition', 'attachment; filename="hecho_' . $hecho->folio_c5i . '.doc"');
    }
}
