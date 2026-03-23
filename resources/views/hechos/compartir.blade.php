@extends('adminlte::page')

@section('title', 'Compartir hecho')

@section('content_header')
    <h1>Compartir hecho</h1>
@stop

@section('content')
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title">Prueba de compartir nativo</h3>
    </div>

    <div class="card-body">
        <p><strong>Instrucción:</strong> abre esta vista desde tu celular para probar el compartir nativo real.</p>

        <div class="mb-3 d-flex flex-wrap" style="gap:10px;">
            <button type="button" class="btn btn-success" onclick="compartirNativo()">
                <i class="fab fa-whatsapp"></i> Compartir
            </button>

            <button type="button" class="btn btn-primary" onclick="copiarTexto()">
                <i class="fa fa-copy"></i> Copiar texto
            </button>

            <a class="btn btn-success"
               target="_blank"
               rel="noopener"
               href="https://wa.me/?text={{ urlencode($message) }}">
                <i class="fab fa-whatsapp"></i> Abrir WhatsApp con texto
            </a>
        </div>

        <div class="form-group">
            <label>Texto a compartir</label>
            <textarea id="mensajeCompartir" class="form-control" rows="10">{{ $message }}</textarea>
        </div>

        @if(!empty($media))
            <hr>
            <h5>Imágenes</h5>
            <div class="row">
                @foreach($media as $img)
                    <div class="col-md-3 col-6 mb-3">
                        <a href="{{ $img }}" target="_blank" rel="noopener">
                            <img src="{{ $img }}" class="img-fluid img-thumbnail" alt="foto">
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <div id="shareStatus" class="mt-3"></div>
    </div>
</div>
@stop

@section('js')
<script>
const MEDIA_URLS = @json($media ?? []);

function setStatus(message, type = 'info') {
    const el = document.getElementById('shareStatus');
    const map = {
        success: 'alert alert-success',
        danger: 'alert alert-danger',
        warning: 'alert alert-warning',
        info: 'alert alert-info'
    };
    el.className = map[type] || map.info;
    el.textContent = message;
}

function clearStatus() {
    const el = document.getElementById('shareStatus');
    el.className = '';
    el.textContent = '';
}

function guessFileName(url, index, mimeType = '') {
    try {
        const cleanUrl = url.split('?')[0];
        const last = cleanUrl.substring(cleanUrl.lastIndexOf('/') + 1) || ('imagen_' + (index + 1));
        if (last.includes('.')) return last;

        if (mimeType === 'image/png') return last + '.png';
        if (mimeType === 'image/webp') return last + '.webp';
        return last + '.jpg';
    } catch (e) {
        if (mimeType === 'image/png') return 'imagen_' + (index + 1) + '.png';
        if (mimeType === 'image/webp') return 'imagen_' + (index + 1) + '.webp';
        return 'imagen_' + (index + 1) + '.jpg';
    }
}

async function urlToFile(url, index) {
    const response = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin'
    });

    if (!response.ok) {
        throw new Error('No se pudo descargar la imagen: ' + url);
    }

    const blob = await response.blob();

    if (!blob.type.startsWith('image/')) {
        throw new Error('El archivo no es una imagen válida: ' + url);
    }

    const filename = guessFileName(url, index, blob.type);

    return new File([blob], filename, {
        type: blob.type,
        lastModified: Date.now()
    });
}

async function obtenerArchivosImagen() {
    const files = [];

    for (let i = 0; i < MEDIA_URLS.length; i++) {
        const file = await urlToFile(MEDIA_URLS[i], i);
        files.push(file);
    }

    return files;
}

async function compartirNativo() {
    clearStatus();

    const text = document.getElementById('mensajeCompartir').value.trim();

    if (!navigator.share) {
        setStatus('Este navegador no soporta compartir nativo. Se abrirá WhatsApp solo con texto.', 'warning');
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
        return;
    }

    try {
        let files = [];

        if (Array.isArray(MEDIA_URLS) && MEDIA_URLS.length > 0) {
            files = await obtenerArchivosImagen();
        }

        const canShareFiles = files.length > 0
            && navigator.canShare
            && navigator.canShare({ files });

        if (canShareFiles) {
            try {
                await navigator.share({
                    title: 'Hecho de tránsito',
                    text: text,
                    files: files
                });
                setStatus('Se abrió el panel de compartir con imágenes.', 'success');
                return;
            } catch (e) {
                console.log('Falló compartir con archivos, se intentará compartir solo texto.', e);
            }
        }

        await navigator.share({
            title: 'Hecho de tránsito',
            text: text
        });

        setStatus('Se abrió el panel de compartir solo con texto.', 'info');

    } catch (e) {
        console.log('Compartir cancelado o no disponible', e);
        setStatus('No se pudo compartir con imágenes desde este dispositivo/app. Usa el botón de WhatsApp con texto como respaldo.', 'warning');
    }
}

async function copiarTexto() {
    try {
        const text = document.getElementById('mensajeCompartir').value;
        await navigator.clipboard.writeText(text);
        setStatus('Texto copiado.', 'success');
    } catch (e) {
        console.log(e);
        setStatus('No se pudo copiar el texto.', 'danger');
    }
}
</script>
@stop
