<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActividadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:ver actividades']);
    }

    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia    = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $query = $this->buildQuery($request, $inicioDia, $finDia);

        $actividades = $query->get();

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.index', compact('actividades', 'categorias', 'fechaSeleccionada'));
    }

    public function informeDiario(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        return $this->informeFecha($fechaSeleccionada, $request);
    }

    public function informeFecha($fecha, Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $tz = 'America/Mexico_City';

        try {
            $fechaSeleccionada = Carbon::createFromFormat('Y-m-d', (string) $fecha, $tz)->toDateString();
        } catch (\Throwable $e) {
            abort(404);
        }

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia    = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $actividades = $this->buildQuery($request, $inicioDia, $finDia)->get();

        $actividades->transform(function ($a) {
            $rel = $this->getOrCreatePdfImage($a->foto_path);
            $a->foto_pdf_path = $rel;

            $a->foto_pdf_abs = $rel
                ? public_path('storage/' . ltrim($rel, '/'))
                : null;

            return $a;
        });

        $pdfFacade = null;
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\Pdf::class;
        } elseif (class_exists(\Barryvdh\DomPDF\Facade\PDF::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\PDF::class;
        }

        if (!$pdfFacade) {
            return back()->with('error', 'No está disponible el generador de PDF en el sistema.');
        }

        $nombreArchivo = 'INFORME_ACTIVIDADES_' . Carbon::parse($fechaSeleccionada, $tz)->format('d-m-Y') . '.pdf';

        $pdf = $pdfFacade::loadView('actividades.informe', [
            'actividades' => $actividades,
            'fechaSeleccionada' => $fechaSeleccionada,
            'tz' => $tz,
        ])->setPaper('letter', 'portrait')
          ->setOptions([
              'dpi' => 96,
              'defaultFont' => 'DejaVu Sans',
              'isRemoteEnabled' => true,
              'chroot' => base_path(),
          ]);

        return $pdf->download($nombreArchivo);
    }

    public function create()
    {
        $this->authorize('crear actividades');

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $this->authorize('crear actividades');

        $validated = $request->validate([
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'nullable|exists:actividad_subcategorias,id',
            'foto'                      => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validated['nombre'] = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');
        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated) {
            $file = $request->file('foto');
            $fotoHash = hash_file('sha256', $file->getRealPath());

            $yaExiste = Actividad::query()->where('foto_hash', $fotoHash)->exists();
            if ($yaExiste) {
                return back()->withErrors([
                    'foto' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                ])->withInput();
            }

            $fotoNombreOriginal = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

            $fotoPath = $file->storeAs('actividades', $filename, 'public');

            $user = Auth::user();

            Actividad::create([
                'actividad_categoria_id'    => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                    => $validated['nombre'],
                'cantidad'                  => 1,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'created_by'                => Auth::id(),
                'updated_by'                => Auth::id(),
                'unidad_org_id'             => $user->unidad_id ?? null,
                'delegacion_id'             => $user->delegacion_id ?? null,
            ]);

            return redirect()->route('actividades.index')->with('success', 'Actividad creada correctamente.');
        });
    }

    public function show(Actividad $actividad)
    {
        $actividad->load(['categoria', 'subcategoria']);
        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $subcategorias = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $actividad->actividad_categoria_id)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        return view('actividades.edit', compact('actividad', 'categorias', 'subcategorias'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $validated = $request->validate([
            'actividad_categoria_id'    => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id' => 'nullable|exists:actividad_subcategorias,id',
            'foto'                      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $validated['nombre'] = mb_strtoupper((string) (Auth::user()->name ?? ''), 'UTF-8');
        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = ActividadSubcategoria::query()
                ->where('id', $validated['actividad_subcategoria_id'])
                ->where('actividad_categoria_id', $validated['actividad_categoria_id'])
                ->exists();

            if (!$ok) {
                return back()->withErrors([
                    'actividad_subcategoria_id' => 'La subcategoría no pertenece a la categoría seleccionada.',
                ])->withInput();
            }
        }

        return DB::transaction(function () use ($request, $validated, $actividad) {
            $fotoPath = $actividad->foto_path;
            $fotoNombreOriginal = $actividad->foto_nombre_original;
            $fotoHash = $actividad->foto_hash;

            if ($request->hasFile('foto')) {
                $file = $request->file('foto');
                $nuevoHash = hash_file('sha256', $file->getRealPath());

                $yaExiste = Actividad::query()
                    ->where('foto_hash', $nuevoHash)
                    ->where('id', '!=', $actividad->id)
                    ->exists();

                if ($yaExiste) {
                    return back()->withErrors([
                        'foto' => 'Esta foto ya fue subida anteriormente (mismo contenido).',
                    ])->withInput();
                }

                if (!empty($fotoPath) && Storage::disk('public')->exists($fotoPath)) {
                    Storage::disk('public')->delete($fotoPath);
                }

                $this->deletePdfCacheForOriginal($fotoPath);

                $fotoNombreOriginal = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;

                $fotoPath = $file->storeAs('actividades', $filename, 'public');
                $fotoHash = $nuevoHash;
            }

            $user = Auth::user();

            $actividad->update([
                'actividad_categoria_id'    => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id' => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                    => $validated['nombre'],
                'cantidad'                  => 1,
                'foto_path'                 => $fotoPath,
                'foto_nombre_original'      => $fotoNombreOriginal,
                'foto_hash'                 => $fotoHash,
                'updated_by'                => Auth::id(),
                'unidad_org_id'             => $actividad->unidad_org_id ?? ($user->unidad_id ?? null),
                'delegacion_id'             => $actividad->delegacion_id ?? ($user->delegacion_id ?? null),
            ]);

            return redirect()->route('actividades.index')->with('success', 'Actividad actualizada correctamente.');
        });
    }

    public function destroy(Actividad $actividad)
    {
        $this->authorize('eliminar actividades');

        return DB::transaction(function () use ($actividad) {
            if (!empty($actividad->foto_path) && Storage::disk('public')->exists($actividad->foto_path)) {
                Storage::disk('public')->delete($actividad->foto_path);
            }

            $this->deletePdfCacheForOriginal($actividad->foto_path);

            $actividad->delete();

            return back()->with('success', 'Actividad eliminada correctamente.');
        });
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $items = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $categoria->id)
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($items);
    }

    private function buildQuery(Request $request, Carbon $inicioDia, Carbon $finDia)
    {
        $query = Actividad::query()
            ->with(['categoria', 'subcategoria'])
            ->whereBetween('created_at', [$inicioDia, $finDia])
            ->orderByDesc('created_at');

        $usuario = Auth::user();

        $this->applyActividadesVisibilityScope($query, $usuario);

        if ($request->filled('actividad_categoria_id')) {
            $query->where('actividad_categoria_id', (int) $request->actividad_categoria_id);
        }

        if ($request->filled('actividad_subcategoria_id')) {
            $query->where('actividad_subcategoria_id', (int) $request->actividad_subcategoria_id);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where('nombre', 'like', "%{$q}%");
        }

        return $query;
    }

    private function applyActividadesVisibilityScope($query, $usuario): void
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $UNIDAD_CARRETERAS_ID = (int) \App\Models\Unidad::query()
            ->where('slug', 'carreteras')
            ->value('id');

        if ($UNIDAD_CARRETERAS_ID > 0 && $unidadId === $UNIDAD_CARRETERAS_ID) {
            $query->where('unidad_org_id', $UNIDAD_CARRETERAS_ID);
            return;
        }

        if ($unidadId === 2) {
            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $esRegional = \App\Models\Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($usuario->hasRole('Subdirector')) {
                if ($esRegional) {
                    $ids = \App\Models\Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $query->whereIn('delegacion_id', $ids);
                } else {
                    $query->where('delegacion_id', $delegacionId);
                }
            } else {
                $query->where('delegacion_id', $delegacionId);
            }

            return;
        }

        if ($unidadId > 0) {
            $query->where('unidad_org_id', $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function getOrCreatePdfImage(?string $fotoPath): ?string
    {
        if (!$fotoPath) return null;

        $disk = Storage::disk('public');
        if (!$disk->exists($fotoPath)) return null;

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));
        if (!is_file($absOriginal)) return null;

        $hash = @hash_file('sha1', $absOriginal);
        if (!$hash) return $fotoPath;

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';
        if ($disk->exists($cacheRel)) return $cacheRel;

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $hash . '.jpg';

        $ok = $this->resizeToJpeg($absOriginal, $tmpOut, 1280, 75);
        if (!$ok || !is_file($tmpOut)) {
            return $fotoPath;
        }

        $disk->put($cacheRel, file_get_contents($tmpOut));
        @unlink($tmpOut);

        return $cacheRel;
    }

    private function resizeToJpeg(string $src, string $dst, int $maxW, int $quality): bool
    {
        $info = @getimagesize($src);
        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) return false;

        $w = (int) $info[0];
        $h = (int) $info[1];
        $mime = (string) $info['mime'];

        if ($w <= 0 || $h <= 0) return false;

        $create = null;
        if ($mime === 'image/jpeg') $create = 'imagecreatefromjpeg';
        if ($mime === 'image/png')  $create = 'imagecreatefrompng';
        if ($mime === 'image/webp') $create = 'imagecreatefromwebp';

        if (!$create || !function_exists($create)) return false;

        $srcIm = @$create($src);
        if (!$srcIm) return false;

        $newW = $w > $maxW ? $maxW : $w;
        $newH = (int) round($h * ($newW / $w));

        $dstIm = imagecreatetruecolor($newW, $newH);
        if (!$dstIm) {
            imagedestroy($srcIm);
            return false;
        }

        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $saved = imagejpeg($dstIm, $dst, $quality);

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        return (bool) $saved;
    }

    private function deletePdfCacheForOriginal(?string $fotoPath): void
    {
        if (!$fotoPath) return;

        $disk = Storage::disk('public');
        if (!$disk->exists($fotoPath)) return;

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));
        if (!is_file($absOriginal)) return;

        $hash = @hash_file('sha1', $absOriginal);
        if (!$hash) return;

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';
        if ($disk->exists($cacheRel)) {
            $disk->delete($cacheRel);
        }
    }
}
