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
            'carretera'   => 'required|string|max:255',
            'nombre'      => 'required|string|max:255',
            'km_inicio'   => 'nullable|numeric',
            'km_fin'      => 'nullable|numeric',
            'lat_inicio'  => 'nullable|numeric|between:-90,90',
            'lng_inicio'  => 'nullable|numeric|between:-180,180',
            'lat_fin'     => 'nullable|numeric|between:-90,90',
            'lng_fin'     => 'nullable|numeric|between:-180,180',
            'polyline'    => 'nullable|string',
            'puntos_json' => 'nullable',
            'activo'      => 'nullable|integer',
        ]);

        $carretera = $request->input('carretera');
        $nombre = $request->input('nombre');

        $kmInicio = $request->filled('km_inicio') ? (float) $request->input('km_inicio') : null;
        $kmFin = $request->filled('km_fin') ? (float) $request->input('km_fin') : null;

        if (!is_null($kmInicio) && !is_null($kmFin) && $kmInicio > $kmFin) {
            return back()->withInput()->withErrors([
                'km_inicio' => 'El KM inicio no puede ser mayor que el KM fin.'
            ]);
        }

        $latInicio = $request->filled('lat_inicio') ? (float) $request->input('lat_inicio') : null;
        $lngInicio = $request->filled('lng_inicio') ? (float) $request->input('lng_inicio') : null;
        $latFin = $request->filled('lat_fin') ? (float) $request->input('lat_fin') : null;
        $lngFin = $request->filled('lng_fin') ? (float) $request->input('lng_fin') : null;

        $polyline = $request->filled('polyline') ? trim((string) $request->input('polyline')) : null;
        $puntosJsonInput = $request->input('puntos_json');

        $anyCoord = !is_null($latInicio) || !is_null($lngInicio) || !is_null($latFin) || !is_null($lngFin);
        $allCoord = !is_null($latInicio) && !is_null($lngInicio) && !is_null($latFin) && !is_null($lngFin);

        if ($anyCoord && !$allCoord) {
            return back()->withInput()->withErrors([
                'lat_inicio' => 'Si capturas coordenadas, debes capturar inicio y fin completos (lat/lng).'
            ]);
        }

        $points = $this->resolvePoints($puntosJsonInput, $polyline, $latInicio, $lngInicio, $latFin, $lngFin);

        if ($puntosJsonInput && empty($points)) {
            return back()->withInput()->withErrors([
                'puntos_json' => 'El campo puntos_json no tiene un formato válido.'
            ]);
        }

        if ($polyline && empty($points)) {
            return back()->withInput()->withErrors([
                'polyline' => 'La polyline no se pudo decodificar correctamente.'
            ]);
        }

        if (!empty($points)) {
            $firstPoint = $points[0];
            $lastPoint = $points[count($points) - 1];

            $latInicio = $firstPoint['lat'];
            $lngInicio = $firstPoint['lng'];
            $latFin = $lastPoint['lat'];
            $lngFin = $lastPoint['lng'];
        }

        $data = [
            'carretera'   => $carretera,
            'nombre'      => $nombre,
            'km_inicio'   => $kmInicio,
            'km_fin'      => $kmFin,
            'lat_inicio'  => $latInicio,
            'lng_inicio'  => $lngInicio,
            'lat_fin'     => $latFin,
            'lng_fin'     => $lngFin,
            'polyline'    => $polyline,
            'puntos_json' => !empty($points) ? $points : null,
            'activo'      => $request->filled('activo') ? (int) $request->input('activo') : 1,
            'geom'        => null,
            'bbox'        => null,
        ];

        DB::beginTransaction();

        try {
            $tramo = Tramo::create($data);

            $this->persistSpatialFields($tramo->id, $points);

            DB::commit();

            return redirect()->route('tramos.index')->with('success', 'Tramo creado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Ocurrió un error al guardar el tramo: ' . $e->getMessage()
            ]);
        }
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
            'carretera'   => 'required|string|max:255',
            'nombre'      => 'required|string|max:255',
            'km_inicio'   => 'nullable|numeric',
            'km_fin'      => 'nullable|numeric',
            'lat_inicio'  => 'nullable|numeric|between:-90,90',
            'lng_inicio'  => 'nullable|numeric|between:-180,180',
            'lat_fin'     => 'nullable|numeric|between:-90,90',
            'lng_fin'     => 'nullable|numeric|between:-180,180',
            'polyline'    => 'nullable|string',
            'puntos_json' => 'nullable',
            'activo'      => 'nullable|integer',
        ]);

        $tramo = Tramo::findOrFail($id);

        $carretera = $request->input('carretera');
        $nombre = $request->input('nombre');

        $kmInicio = $request->filled('km_inicio') ? (float) $request->input('km_inicio') : null;
        $kmFin = $request->filled('km_fin') ? (float) $request->input('km_fin') : null;

        if (!is_null($kmInicio) && !is_null($kmFin) && $kmInicio > $kmFin) {
            return back()->withInput()->withErrors([
                'km_inicio' => 'El KM inicio no puede ser mayor que el KM fin.'
            ]);
        }

        $latInicio = $request->filled('lat_inicio') ? (float) $request->input('lat_inicio') : null;
        $lngInicio = $request->filled('lng_inicio') ? (float) $request->input('lng_inicio') : null;
        $latFin = $request->filled('lat_fin') ? (float) $request->input('lat_fin') : null;
        $lngFin = $request->filled('lng_fin') ? (float) $request->input('lng_fin') : null;

        $polyline = $request->filled('polyline') ? trim((string) $request->input('polyline')) : null;
        $puntosJsonInput = $request->input('puntos_json');

        $anyCoord = !is_null($latInicio) || !is_null($lngInicio) || !is_null($latFin) || !is_null($lngFin);
        $allCoord = !is_null($latInicio) && !is_null($lngInicio) && !is_null($latFin) && !is_null($lngFin);

        if ($anyCoord && !$allCoord) {
            return back()->withInput()->withErrors([
                'lat_inicio' => 'Si capturas coordenadas, debes capturar inicio y fin completos (lat/lng).'
            ]);
        }

        $points = $this->resolvePoints($puntosJsonInput, $polyline, $latInicio, $lngInicio, $latFin, $lngFin);

        if ($puntosJsonInput && empty($points)) {
            return back()->withInput()->withErrors([
                'puntos_json' => 'El campo puntos_json no tiene un formato válido.'
            ]);
        }

        if ($polyline && empty($points)) {
            return back()->withInput()->withErrors([
                'polyline' => 'La polyline no se pudo decodificar correctamente.'
            ]);
        }

        if (!empty($points)) {
            $firstPoint = $points[0];
            $lastPoint = $points[count($points) - 1];

            $latInicio = $firstPoint['lat'];
            $lngInicio = $firstPoint['lng'];
            $latFin = $lastPoint['lat'];
            $lngFin = $lastPoint['lng'];
        }

        $data = [
            'carretera'   => $carretera,
            'nombre'      => $nombre,
            'km_inicio'   => $kmInicio,
            'km_fin'      => $kmFin,
            'lat_inicio'  => $latInicio,
            'lng_inicio'  => $lngInicio,
            'lat_fin'     => $latFin,
            'lng_fin'     => $lngFin,
            'polyline'    => $polyline,
            'puntos_json' => !empty($points) ? $points : null,
            'activo'      => $request->filled('activo') ? (int) $request->input('activo') : $tramo->activo,
        ];

        DB::beginTransaction();

        try {
            $tramo->update($data);

            $this->persistSpatialFields($tramo->id, $points);

            DB::commit();

            return redirect()->route('tramos.index')->with('success', 'Tramo actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Ocurrió un error al actualizar el tramo: ' . $e->getMessage()
            ]);
        }
    }

    public function destroy($id)
    {
        $tramo = Tramo::findOrFail($id);
        $tramo->delete();

        return redirect()->route('tramos.index')->with('success', 'Tramo eliminado correctamente.');
    }

    private function persistSpatialFields($tramoId, array $points): void
    {
        if (count($points) < 2) {
            DB::table('tramos')
                ->where('id', $tramoId)
                ->update([
                    'geom' => null,
                    'bbox' => null,
                ]);
            return;
        }

        $wktLine = $this->makeLineStringFromPointsWkt($points);
        $wktBbox = $this->makeBboxFromPointsWkt($points);

        DB::update(
            "UPDATE tramos
             SET geom = ST_SRID(ST_GeomFromText(?), 4326),
                 bbox = ST_SRID(ST_GeomFromText(?), 4326)
             WHERE id = ?",
            [$wktLine, $wktBbox, $tramoId]
        );
    }

    private function resolvePoints($puntosJsonInput, ?string $polyline, $latInicio, $lngInicio, $latFin, $lngFin): array
    {
        $points = [];

        if (!empty($puntosJsonInput)) {
            $points = $this->parsePointsJson($puntosJsonInput);
        }

        if (empty($points) && !empty($polyline)) {
            $points = $this->decodePolyline($polyline);
        }

        if (empty($points) && !is_null($latInicio) && !is_null($lngInicio) && !is_null($latFin) && !is_null($lngFin)) {
            $points = [
                [
                    'lat' => (float) $latInicio,
                    'lng' => (float) $lngInicio,
                ],
                [
                    'lat' => (float) $latFin,
                    'lng' => (float) $lngFin,
                ],
            ];
        }

        return $this->normalizePoints($points);
    }

    private function parsePointsJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizePoints(array $points): array
    {
        $normalized = [];

        foreach ($points as $point) {
            if (!is_array($point)) {
                continue;
            }

            $lat = null;
            $lng = null;

            if (array_key_exists('lat', $point) && array_key_exists('lng', $point)) {
                $lat = $point['lat'];
                $lng = $point['lng'];
            } elseif (array_key_exists(0, $point) && array_key_exists(1, $point)) {
                $lat = $point[0];
                $lng = $point[1];
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }

            $lat = (float) $lat;
            $lng = (float) $lng;

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            $normalized[] = [
                'lat' => $lat,
                'lng' => $lng,
            ];
        }

        if (count($normalized) < 2) {
            return [];
        }

        return array_values($normalized);
    }

    private function makeLineStringFromPointsWkt(array $points): string
    {
        $segments = [];

        foreach ($points as $point) {
            $segments[] = $this->fmt($point['lng']) . ' ' . $this->fmt($point['lat']);
        }

        return 'LINESTRING(' . implode(', ', $segments) . ')';
    }

    private function makeBboxFromPointsWkt(array $points): string
    {
        $lngs = array_column($points, 'lng');
        $lats = array_column($points, 'lat');

        $lngMin = $this->fmt(min($lngs));
        $lngMax = $this->fmt(max($lngs));
        $latMin = $this->fmt(min($lats));
        $latMax = $this->fmt(max($lats));

        return "POLYGON(($lngMin $latMin, $lngMax $latMin, $lngMax $latMax, $lngMin $latMax, $lngMin $latMin))";
    }

    private function decodePolyline(string $encoded): array
    {
        $points = [];
        $index = 0;
        $lat = 0;
        $lng = 0;
        $length = strlen($encoded);

        while ($index < $length) {
            $result = 0;
            $shift = 0;

            do {
                if ($index >= $length) {
                    return [];
                }

                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $deltaLat = ($result & 1) ? ~(int)($result >> 1) : (int)($result >> 1);
            $lat += $deltaLat;

            $result = 0;
            $shift = 0;

            do {
                if ($index >= $length) {
                    return [];
                }

                $b = ord($encoded[$index++]) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $deltaLng = ($result & 1) ? ~(int)($result >> 1) : (int)($result >> 1);
            $lng += $deltaLng;

            $points[] = [
                'lat' => $lat / 1e5,
                'lng' => $lng / 1e5,
            ];
        }

        return $points;
    }

    private function fmt($n): string
    {
        return rtrim(rtrim(number_format((float) $n, 7, '.', ''), '0'), '.');
    }
}
