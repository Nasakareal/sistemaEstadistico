<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PersonalFotoController extends Controller
{
    public function show(Personal $personal, PersonalFoto $foto)
    {
        $this->abortUnlessCanView($personal);

        if ((int) $foto->personal_id !== (int) $personal->id) {
            abort(404);
        }

        return $this->streamFoto($foto->ruta, $foto->mime_type, $foto->nombre_original);
    }

    public function showPrincipal(Personal $personal)
    {
        $this->abortUnlessCanView($personal);

        $ruta = $personal->foto ?: optional($personal->fotoPrincipal)->ruta;

        if (!$ruta) {
            abort(404);
        }

        return $this->streamFoto($ruta);
    }

    public function showSigned(PersonalFoto $foto)
    {
        return $this->streamFoto($foto->ruta, $foto->mime_type, $foto->nombre_original);
    }

    public function store(Request $request, Personal $personal)
    {
        $this->abortUnlessCanView($personal);

        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'fotos' => 'nullable|array',
            'fotos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $archivos = [];

        if ($request->hasFile('foto')) {
            $archivos[] = $request->file('foto');
        }

        foreach ((array) $request->file('fotos', []) as $archivo) {
            if ($archivo) {
                $archivos[] = $archivo;
            }
        }

        if (!$archivos) {
            throw ValidationException::withMessages([
                'foto' => 'Selecciona al menos una foto.',
            ]);
        }

        try {
            $primeraRuta = null;

            foreach ($archivos as $archivo) {
                $ruta = $this->guardarFotoPrivada($archivo);
                if ($primeraRuta === null) {
                    $primeraRuta = $ruta;
                }

                $personal->fotos()->create([
                    'ruta' => $ruta,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getClientMimeType(),
                    'tamano' => $archivo->getSize(),
                ]);
            }

            if ($primeraRuta) {
                $personal->update(['foto' => $primeraRuta]);
            }

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Foto registrada correctamente.');
        } catch (Throwable $e) {
            Log::error('Error al registrar foto de personal: ' . $e->getMessage(), [
                'personal_id' => $personal->id,
                'exception' => $e,
            ]);

            return back()
                ->withErrors('Hubo un error al registrar la foto. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy(Personal $personal, PersonalFoto $foto)
    {
        $this->abortUnlessCanView($personal);

        if ((int) $foto->personal_id !== (int) $personal->id) {
            abort(404);
        }

        try {
            if ($foto->ruta && Storage::disk('local')->exists($foto->ruta)) {
                Storage::disk('local')->delete($foto->ruta);
            }

            if ($foto->ruta && Storage::disk('public')->exists($foto->ruta)) {
                Storage::disk('public')->delete($foto->ruta);
            }

            $rutaEliminada = $foto->ruta;
            $foto->delete();

            if ($personal->foto === $rutaEliminada) {
                $nuevaPrincipal = $personal->fotos()
                    ->latest('id')
                    ->first();

                $personal->update([
                    'foto' => $nuevaPrincipal ? $nuevaPrincipal->ruta : null,
                ]);
            }

            return redirect()
                ->route('personal.show', $personal->id)
                ->with('success', 'Foto eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar foto de personal: ' . $e->getMessage());

            return back()->withErrors('Hubo un error al eliminar la foto.');
        }
    }

    private function abortUnlessCanView(Personal $personal): void
    {
        $actor = Auth::user();

        if (!$actor) {
            abort(401);
        }

        if ($actor->hasRole('Superadmin') || (int) ($actor->unidad_id ?? 0) === 3) {
            return;
        }

        if ((int) ($actor->unidad_id ?? 0) === (int) ($personal->unidad_id ?? 0)) {
            return;
        }

        abort(404);
    }

    private function streamFoto(?string $ruta, ?string $mimeType = null, ?string $nombre = null)
    {
        $ruta = ltrim(str_replace('\\', '/', (string) $ruta), '/');

        if ($ruta === '' || strpos($ruta, '..') !== false || !str_starts_with($ruta, 'personals/fotos/')) {
            abort(404);
        }

        if (Storage::disk('local')->exists($ruta)) {
            return Storage::disk('local')->response($ruta, $nombre ?: basename($ruta), $this->headersArchivo('local', $ruta, $mimeType), 'inline');
        }

        if (Storage::disk('public')->exists($ruta)) {
            return Storage::disk('public')->response($ruta, $nombre ?: basename($ruta), $this->headersArchivo('public', $ruta, $mimeType), 'inline');
        }

        abort(404);
    }

    private function guardarFotoPrivada($archivo): string
    {
        $ruta = $archivo->store('personals/fotos', 'local');

        if (!is_string($ruta) || trim($ruta) === '') {
            throw new RuntimeException('No se pudo guardar la foto de personal en almacenamiento privado.');
        }

        return str_replace('\\', '/', $ruta);
    }

    private function headersArchivo(string $disk, string $ruta, ?string $mimeType = null): array
    {
        return [
            'Content-Type' => $mimeType ?: (Storage::disk($disk)->mimeType($ruta) ?: 'application/octet-stream'),
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
