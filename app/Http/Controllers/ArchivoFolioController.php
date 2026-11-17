<?php

namespace App\Http\Controllers;

use App\Models\ArchivoFolio;
use App\Models\ArchivoLibro;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArchivoFolioController extends Controller
{
    public function index(ArchivoLibro $libro)
    {
        $folios = $libro->folios()->with('documento')->orderBy('numero_pagina')->paginate(50);
        return view('admin.settings.archivo_siniestros.folios.index', compact('libro', 'folios'));
    }

    public function store(Request $request, ArchivoLibro $libro)
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'between:2010,2100'],
            'folio' => ['required', 'string', 'max:80'],
            'fecha_siniestro' => ['nullable', 'date'],
            'numero_pagina' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'documento_id' => ['nullable', Rule::exists('archivo_documentos', 'id')->where('libro_id', $libro->id)],
            'pagina_documento' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);
        if ($libro->total_paginas && isset($datos['numero_pagina'])
            && (int) $datos['numero_pagina'] > (int) $libro->total_paginas) {
            return back()->withErrors(['numero_pagina' => 'La página está fuera del libro.'])->withInput();
        }
        if (isset($datos['pagina_documento']) && empty($datos['documento_id'])) {
            return back()->withErrors(['documento_id' => 'Selecciona el documento al indicar una página digital.'])->withInput();
        }
        $datos['estado_verificacion'] = 'pendiente';
        $libro->folios()->create($datos);
        return redirect()->route('archivo_siniestros.libros.folios.index', $libro)->with('success', 'Folio registrado.');
    }
}
