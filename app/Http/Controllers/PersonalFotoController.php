<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\PersonalFoto;
use App\Services\Fotos\PersonalFotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonalFotoController extends Controller
{
    private $fotoStorage;

    public function __construct(PersonalFotoStorage $fotoStorage)
    {
        $this->fotoStorage = $fotoStorage;
    }

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

    public function showPrincipalSigned(Personal $personal)
    {
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
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,webp,heic,heif,avif,bmp,gif,tif,tiff|max:10240',
            'fotos' => 'nullable|array',
            'fotos.*' => 'file|mimes:jpg,jpeg,png,webp,heic,heif,avif,bmp,gif,tif,tiff|max:10240',
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
            $rutaEliminada = $foto->ruta;
            $this->fotoStorage->delete($rutaEliminada);
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
        $ruta = $this->fotoStorage->normalizePath($ruta);

        if ($ruta === '' || strpos($ruta, '..') !== false || !str_starts_with($ruta, 'personal/')) {
            abort(404);
        }

        return $this->fotoStorage->response($ruta, $mimeType, $nombre ?: basename($ruta));
    }

    private function guardarFotoPrivada($archivo): string
    {
        return $this->fotoStorage->putUploadedFile($archivo);
    }
}
