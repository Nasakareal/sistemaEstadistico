<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use App\Models\TutorialCategoria;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TutorialController extends Controller
{
    private const PLATAFORMAS = [
        Tutorial::PLATAFORMA_APP_MOVIL => 'App movil',
        Tutorial::PLATAFORMA_WEB => 'Web',
        Tutorial::PLATAFORMA_AMBAS => 'Ambas',
    ];

    public function index(Request $request)
    {
        $tutoriales = Tutorial::query()
            ->with(['categoria', 'unidad'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim((string) $request->input('q'));
                $query->where(function ($inner) use ($q) {
                    $inner
                        ->where('titulo', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%")
                        ->orWhere('youtube_url', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                $query->where('tutorial_categoria_id', (int) $request->input('categoria_id'));
            })
            ->when($request->filled('plataforma'), function ($query) use ($request) {
                $query->where('plataforma', $request->input('plataforma'));
            })
            ->when($request->filled('unidad_id'), function ($query) use ($request) {
                $query->where('unidad_id', (int) $request->input('unidad_id'));
            })
            ->when($request->filled('estado'), function ($query) use ($request) {
                $query->where('activo', $request->input('estado') === 'activo');
            })
            ->orderByRaw('COALESCE(unidad_id, 0)')
            ->orderByRaw('COALESCE(tutorial_categoria_id, 0)')
            ->orderBy('orden')
            ->orderBy('titulo')
            ->paginate(20)
            ->withQueryString();

        return view('admin.settings.tutoriales.index', [
            'tutoriales' => $tutoriales,
            'categorias' => $this->categorias(),
            'unidades' => $this->unidades(),
            'plataformas' => self::PLATAFORMAS,
            'filtros' => $request->only(['q', 'categoria_id', 'plataforma', 'unidad_id', 'estado']),
        ]);
    }

    public function create()
    {
        return view('admin.settings.tutoriales.create', [
            'tutorial' => new Tutorial([
                'plataforma' => Tutorial::PLATAFORMA_APP_MOVIL,
                'activo' => true,
                'orden' => 0,
            ]),
            'categorias' => $this->categorias(),
            'unidades' => $this->unidades(),
            'plataformas' => self::PLATAFORMAS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['tutorial_categoria_id'] = $this->resolveCategoriaId($request);
        $data['youtube_video_id'] = $this->youtubeVideoId($data['youtube_url']);

        Tutorial::create($data);

        return redirect()
            ->route('settings.tutoriales.index')
            ->with('success', 'Tutorial creado correctamente.');
    }

    public function edit(Tutorial $tutorial)
    {
        return view('admin.settings.tutoriales.edit', [
            'tutorial' => $tutorial->load(['categoria', 'unidad']),
            'categorias' => $this->categorias(),
            'unidades' => $this->unidades(),
            'plataformas' => self::PLATAFORMAS,
        ]);
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $data = $this->validatedData($request);
        $data['tutorial_categoria_id'] = $this->resolveCategoriaId($request);
        $data['youtube_video_id'] = $this->youtubeVideoId($data['youtube_url']);

        $tutorial->update($data);

        return redirect()
            ->route('settings.tutoriales.index')
            ->with('success', 'Tutorial actualizado correctamente.');
    }

    public function destroy(Tutorial $tutorial)
    {
        $tutorial->delete();

        return redirect()
            ->route('settings.tutoriales.index')
            ->with('success', 'Tutorial eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge([
            'youtube_url' => $this->normalizeUrl((string) $request->input('youtube_url', '')),
        ]);

        $validated = $request->validate([
            'categoria_id' => ['nullable', 'integer', 'exists:tutorial_categorias,id'],
            'categoria_nueva' => ['nullable', 'string', 'max:150'],
            'unidad_id' => ['nullable', 'integer', 'exists:unidades,id'],
            'titulo' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string', 'max:1200'],
            'youtube_url' => ['required', 'url', 'max:500'],
            'plataforma' => ['required', Rule::in(array_keys(self::PLATAFORMAS))],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'activo' => ['nullable', 'boolean'],
        ]);

        return [
            'titulo' => $validated['titulo'],
            'unidad_id' => $validated['unidad_id'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'youtube_url' => $validated['youtube_url'],
            'plataforma' => $validated['plataforma'],
            'orden' => (int) ($validated['orden'] ?? 0),
            'activo' => $request->boolean('activo'),
        ];
    }

    private function categorias()
    {
        return TutorialCategoria::query()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function unidades()
    {
        return Unidad::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();
    }

    private function resolveCategoriaId(Request $request): int
    {
        $nueva = trim((string) $request->input('categoria_nueva', ''));
        if ($nueva !== '') {
            $existente = TutorialCategoria::where('nombre', $nueva)->first();
            if ($existente) {
                return $existente->id;
            }

            $slug = $this->uniqueSlug($nueva);

            return TutorialCategoria::create([
                'nombre' => $nueva,
                'slug' => $slug,
                'orden' => 0,
                'activo' => true,
            ])->id;
        }

        $categoriaId = (int) $request->input('categoria_id', 0);
        if ($categoriaId > 0) {
            return $categoriaId;
        }

        return TutorialCategoria::firstOrCreate(
            ['slug' => 'general'],
            [
                'nombre' => 'General',
                'orden' => 0,
                'activo' => true,
            ]
        )->id;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        if (!preg_match('#^https?://#i', $url)) {
            return 'https://' . $url;
        }

        return $url;
    }

    private function youtubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if (str_contains($host, 'youtu.be') && $path !== '') {
            return Str::limit(explode('/', $path)[0], 40, '');
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            if (!empty($query['v'])) {
                return Str::limit((string) $query['v'], 40, '');
            }

            $segments = array_values(array_filter(explode('/', $path)));
            if (count($segments) >= 2 && in_array($segments[0], ['embed', 'shorts'], true)) {
                return Str::limit($segments[1], 40, '');
            }
        }

        return null;
    }

    private function uniqueSlug(string $nombre): string
    {
        $base = Str::slug($nombre);
        if ($base === '') {
            $base = 'categoria';
        }

        $slug = $base;
        $counter = 2;

        while (TutorialCategoria::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
