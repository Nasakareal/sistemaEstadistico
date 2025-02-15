<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use PDF;

class ConstanciaManejoController extends Controller
{
    public function generarConstancias(Request $request)
    {
        // Validar datos del formulario
        $request->validate([
            'desde' => 'required|integer|min:1',
            'hasta' => 'required|integer|gte:desde',
        ]);

        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        // Ruta de la plantilla en /public/formatos/
        $rutaPlantilla = public_path('formatos/plantilla_constancia.docx');
        if (!file_exists($rutaPlantilla)) {
            return back()->with('error', 'No se encontró la plantilla de constancia.');
        }

        $archivosGenerados = [];

        for ($i = $desde; $i <= $hasta; $i++) {
            $templateProcessor = new TemplateProcessor($rutaPlantilla);
            $templateProcessor->setValue('numero_constancia', $i);

            $nombreArchivo = "constancia_manejo_{$i}.docx";
            $rutaArchivo = storage_path("app/public/formatos/{$nombreArchivo}");

            $templateProcessor->saveAs($rutaArchivo);
            $archivosGenerados[] = asset("storage/formatos/{$nombreArchivo}");
        }

        return view('formatos.constancias_generadas', compact('archivosGenerados'));
    }
}
