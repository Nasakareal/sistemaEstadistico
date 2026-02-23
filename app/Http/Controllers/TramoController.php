<?php

namespace App\Http\Controllers;

use App\Models\Tramo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TramoController extends Controller
{
    public function index()
    {
        $tramos = Tramo::orderByDesc('activo')
            ->orderBy('carretera')
            ->orderBy('nombre')
            ->paginate(25);

        return view('tramos.index', compact('tramos'));
    }

    public function create()
    {
        return view('tramos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'carretera' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'km_inicio' => 'nullable|numeric',
            'km_fin' => 'nullable|numeric',
            'lat_inicio' => 'nullable|numeric|between:-90,90',
            'lng_inicio' => 'nullable|numeric|between:-180,180',
            'lat_fin' => 'nullable|numeric|between:-90,90',
            'lng_fin' => 'nullable|numeric|between:-180,180',
        ]);

        $carretera = $request->input('carretera');
        $nombre = $request->input('nombre');

        $kmInicio = $request->filled('km_inicio') ? (float) $request->input('km_inicio') : null;
        $kmFin = $request->filled('km_fin') ? (float) $request->input('km_fin') : null;

        if (!is_null($kmInicio) && !is_null($kmFin) && $kmInicio > $kmFin) {
            return back()->withInput()->withErrors(['km_inicio' => 'El KM inicio no puede ser mayor que el KM fin.']);
        }

        $latInicio = $request->filled('lat_inicio') ? (float) $request->input('lat_inicio') : null;
        $lngInicio = $request->filled('lng_inicio') ? (float) $request->input('lng_inicio') : null;
        $latFin = $request->filled('lat_fin') ? (float) $request->input('lat_fin') : null;
        $lngFin = $request->filled('lng_fin') ? (float) $request->input('lng_fin') : null;

        $anyCoord = !is_null($latInicio) || !is_null($lngInicio) || !is_null($latFin) || !is_null($lngFin);
        $allCoord = !is_null($latInicio) && !is_null($lngInicio) && !is_null($latFin) && !is_null($lngFin);

        if ($anyCoord && !$allCoord) {
            return back()->withInput()->withErrors(['lat_inicio' => 'Si capturas coordenadas, debes capturar inicio y fin completos (lat/lng).']);
        }

        $data = [
            'carretera' => $carretera,
            'nombre' => $nombre,
            'km_inicio' => $kmInicio,
            'km_fin' => $kmFin,
            'activo' => 1,
            'lat_inicio' => $latInicio,
            'lng_inicio' => $lngInicio,
            'lat_fin' => $latFin,
            'lng_fin' => $lngFin,
            'geom' => null,
            'bbox' => null,
        ];

        if ($allCoord) {
            $wktLine = $this->makeLineStringWkt($lngInicio, $latInicio, $lngFin, $latFin);
            $wktBbox = $this->makeBboxPolygonWkt($lngInicio, $latInicio, $lngFin, $latFin);

            $data['geom'] = DB::raw("ST_SRID(ST_GeomFromText(" . $this->quote($wktLine) . "), 4326)");
            $data['bbox'] = DB::raw("ST_SRID(ST_GeomFromText(" . $this->quote($wktBbox) . "), 4326)");
        }

        Tramo::create($data);

        return redirect()->route('tramos.index')->with('success', 'Tramo creado correctamente.');
    }

    public function show($id)
    {
        $tramo = Tramo::findOrFail($id);
        return view('tramos.show', compact('tramo'));
    }

    public function edit($id)
    {
        $tramo = Tramo::findOrFail($id);
        return view('tramos.edit', compact('tramo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'carretera' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'km_inicio' => 'nullable|numeric',
            'km_fin' => 'nullable|numeric',
            'lat_inicio' => 'nullable|numeric|between:-90,90',
            'lng_inicio' => 'nullable|numeric|between:-180,180',
            'lat_fin' => 'nullable|numeric|between:-90,90',
            'lng_fin' => 'nullable|numeric|between:-180,180',
        ]);

        $tramo = Tramo::findOrFail($id);

        $carretera = $request->input('carretera');
        $nombre = $request->input('nombre');

        $kmInicio = $request->filled('km_inicio') ? (float) $request->input('km_inicio') : null;
        $kmFin = $request->filled('km_fin') ? (float) $request->input('km_fin') : null;

        if (!is_null($kmInicio) && !is_null($kmFin) && $kmInicio > $kmFin) {
            return back()->withInput()->withErrors(['km_inicio' => 'El KM inicio no puede ser mayor que el KM fin.']);
        }

        $latInicio = $request->filled('lat_inicio') ? (float) $request->input('lat_inicio') : null;
        $lngInicio = $request->filled('lng_inicio') ? (float) $request->input('lng_inicio') : null;
        $latFin = $request->filled('lat_fin') ? (float) $request->input('lat_fin') : null;
        $lngFin = $request->filled('lng_fin') ? (float) $request->input('lng_fin') : null;

        $anyCoord = !is_null($latInicio) || !is_null($lngInicio) || !is_null($latFin) || !is_null($lngFin);
        $allCoord = !is_null($latInicio) && !is_null($lngInicio) && !is_null($latFin) && !is_null($lngFin);

        if ($anyCoord && !$allCoord) {
            return back()->withInput()->withErrors(['lat_inicio' => 'Si capturas coordenadas, debes capturar inicio y fin completos (lat/lng).']);
        }

        $data = [
            'carretera' => $carretera,
            'nombre' => $nombre,
            'km_inicio' => $kmInicio,
            'km_fin' => $kmFin,
            'activo' => 1,
            'lat_inicio' => $latInicio,
            'lng_inicio' => $lngInicio,
            'lat_fin' => $latFin,
            'lng_fin' => $lngFin,
            'geom' => null,
            'bbox' => null,
        ];

        if ($allCoord) {
            $wktLine = $this->makeLineStringWkt($lngInicio, $latInicio, $lngFin, $latFin);
            $wktBbox = $this->makeBboxPolygonWkt($lngInicio, $latInicio, $lngFin, $latFin);

            $data['geom'] = DB::raw("ST_SRID(ST_GeomFromText(" . $this->quote($wktLine) . "), 4326)");
            $data['bbox'] = DB::raw("ST_SRID(ST_GeomFromText(" . $this->quote($wktBbox) . "), 4326)");
        }

        $tramo->update($data);

        return redirect()->route('tramos.index')->with('success', 'Tramo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $tramo = Tramo::findOrFail($id);
        $tramo->delete();

        return redirect()->route('tramos.index')->with('success', 'Tramo eliminado correctamente.');
    }

    private function makeLineStringWkt($lng1, $lat1, $lng2, $lat2)
    {
        $lng1 = $this->fmt($lng1);
        $lat1 = $this->fmt($lat1);
        $lng2 = $this->fmt($lng2);
        $lat2 = $this->fmt($lat2);

        return "LINESTRING($lng1 $lat1, $lng2 $lat2)";
    }

    private function makeBboxPolygonWkt($lng1, $lat1, $lng2, $lat2)
    {
        $lngMin = $this->fmt(min((float)$lng1, (float)$lng2));
        $lngMax = $this->fmt(max((float)$lng1, (float)$lng2));
        $latMin = $this->fmt(min((float)$lat1, (float)$lat2));
        $latMax = $this->fmt(max((float)$lat1, (float)$lat2));

        return "POLYGON(($lngMin $latMin, $lngMax $latMin, $lngMax $latMax, $lngMin $latMax, $lngMin $latMin))";
    }

    private function fmt($n)
    {
        return rtrim(rtrim(number_format((float)$n, 7, '.', ''), '0'), '.');
    }

    private function quote($s)
    {
        return DB::getPdo()->quote($s);
    }
}
