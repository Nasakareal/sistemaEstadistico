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

        <div class="mb-3">
            <button type="button" class="btn btn-success" onclick="compartirNativo()">
                <i class="fab fa-whatsapp"></i> Compartir
            </button>

            <button type="button" class="btn btn-primary" onclick="copiarTexto()">
                <i class="fa fa-copy"></i> Copiar texto
            </button>

            <a class="btn btn-success"
               target="_blank"
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
                    <div class="col-md-3 mb-3">
                        <a href="{{ $img }}" target="_blank">
                            <img src="{{ $img }}" class="img-fluid img-thumbnail" alt="foto">
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@stop

@section('js')
<script>
async function compartirNativo() {
    const text = document.getElementById('mensajeCompartir').value;

    if (navigator.share) {
        try {
            await navigator.share({
                text: text
            });
        } catch (e) {
            console.log('Compartir cancelado o no disponible', e);
        }
    } else {
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank');
    }
}

async function copiarTexto() {
    const text = document.getElementById('mensajeCompartir').value;
    await navigator.clipboard.writeText(text);
    alert('Texto copiado');
}
</script>
@stop
