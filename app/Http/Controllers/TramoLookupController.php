<?php

namespace App\Http\Controllers;

use App\Models\Grua;
use App\Models\Tramo;
use App\Models\GruaGuardia;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TramoLookupController extends Controller
{
    public function index()
    {
        return view('tramos_lookup.index');
    }

    public function resolve(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ]);

        $q = strtoupper(trim($data['query']));
        $hoy = now()->toDateString();

        $guardiaSemana = GruaGuardia::with('grua')
            ->where('activo', 1)
            ->where('week_start', '<=', $hoy)
            ->where('week_end', '>=', $hoy)
            ->first();

        $gruaGuardia = $guardiaSemana ? $guardiaSemana->grua : null;

        $tramoDetectado = $this->detectarTramoPorTexto($q);

        $gruaConcesionada = null;

        if ($tramoDetectado) {
            $gruaConcesionada = $tramoDetectado->gruas()
                ->wherePivot('activo', 1)
                ->where(function ($qq) use ($hoy) {
                    $qq->whereNull('grua_tramo.desde')->orWhere('grua_tramo.desde', '<=', $hoy);
                })
                ->where(function ($qq) use ($hoy) {
                    $qq->whereNull('grua_tramo.hasta')->orWhere('grua_tramo.hasta', '>=', $hoy);
                })
                ->orderBy('grua_tramo.prioridad')
                ->first();
        }

        $gruaSugerida = $gruaConcesionada ?: $gruaGuardia;

        return view('tramos_lookup.index', [
            'query' => $data['query'],
            'tramo' => $tramoDetectado,
            'gruaConcesionada' => $gruaConcesionada,
            'gruaGuardia' => $gruaGuardia,
            'gruaSugerida' => $gruaSugerida,
        ]);
    }

    private function detectarTramoPorTexto(string $q): ?Tramo
    {
        $tieneKm7 = str_contains($q, '7+000') || str_contains($q, 'KM 7') || str_contains($q, 'KILOMETRO 7');
        $tieneMoreliaPatz = str_contains($q, 'MORELIA') && (str_contains($q, 'PATZCUARO') || str_contains($q, 'PÁTZCUARO'));
        $tieneBorucas = str_contains($q, 'BORUCAS') || str_contains($q, '"BORUCAS"') || str_contains($q, 'BORUCA');
        $tieneHigareda = str_contains($q, 'HIGAREDA');

        if (($tieneKm7 && $tieneMoreliaPatz) || $tieneBorucas || $tieneHigareda) {
            $tramo = Tramo::where('activo', 1)
                ->where(function ($qq) {
                    $qq->where('nombre', 'like', '%KM 7%')
                       ->orWhere('nombre', 'like', '%7+000%')
                       ->orWhere('nombre', 'like', '%BORUCAS%')
                       ->orWhere('nombre', 'like', '%HIGAREDA%');
                })
                ->orderBy('km_inicio')
                ->first();

            if ($tramo) return $tramo;
        }

        $tramo2 = Tramo::where('activo', 1)
            ->where(function ($qq) use ($q) {
                $qq->whereRaw('UPPER(nombre) like ?', ['%' . $q . '%'])
                   ->orWhereRaw('UPPER(carretera) like ?', ['%' . $q . '%']);
            })
            ->orderBy('carretera')
            ->orderBy('km_inicio')
            ->first();

        return $tramo2;
    }
}
