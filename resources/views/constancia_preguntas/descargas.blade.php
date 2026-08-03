@extends('adminlte::page')

@section('title', 'Descargar Exámenes Escritos')

@section('content_header')
    <div class="download-header">
        <h1>Descargar Exámenes Escritos</h1>
        <p>Elige el tipo de licencia y descarga el examen listo para imprimir.</p>
    </div>
@stop

@section('content')
<div class="download-wrapper">
    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="download-card">
        <div class="download-card-header">
            <i class="fa-solid fa-file-pdf"></i>
            <div>
                <h3>Selecciona el tipo de examen</h3>
                <span>Cada archivo contiene 20 preguntas y se descarga en formato PDF.</span>
            </div>
        </div>

        <div class="download-grid">
            @foreach($tiposExamen as $examen)
                @if($examen['total'] >= 20)
                    <a href="{{ route('constancias_manejo.preguntas.descargar', $examen['tipo']) }}"
                       class="exam-download-button">
                        <i class="fa-solid fa-download"></i>
                        <span>Descargar {{ $examen['label'] }}</span>
                        <small>PDF · 20 preguntas</small>
                    </a>
                @else
                    <div class="exam-download-button disabled" aria-disabled="true">
                        <i class="fa-solid fa-lock"></i>
                        <span>{{ $examen['label'] }}</span>
                        <small>No disponible · {{ $examen['total'] }} de 20 preguntas</small>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.download-header {
    margin-bottom: 18px;
    text-align: center;
}

.download-header h1 {
    color: #f8fafc;
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 4px;
}

.download-header p {
    color: #cbd5e1;
    font-size: 16px;
    margin: 0;
}

.download-wrapper {
    margin: 0 auto;
    max-width: 920px;
    padding: 8px 15px 36px;
}

.download-card {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 18px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, .45);
    overflow: hidden;
}

.download-card-header {
    align-items: center;
    background: #1e293b;
    border-bottom: 1px solid #334155;
    display: flex;
    gap: 16px;
    padding: 24px 28px;
}

.download-card-header > i {
    color: #60a5fa;
    font-size: 38px;
}

.download-card-header h3 {
    color: #f8fafc;
    font-size: 22px;
    font-weight: 750;
    margin: 0 0 3px;
}

.download-card-header span {
    color: #cbd5e1;
}

.download-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    padding: 28px;
}

.exam-download-button {
    align-items: center;
    background: linear-gradient(135deg, #075985, #1d4ed8);
    border: 1px solid #38bdf8;
    border-radius: 14px;
    color: #fff !important;
    display: grid;
    gap: 2px 12px;
    grid-template-columns: 34px 1fr;
    min-height: 92px;
    padding: 17px 20px;
    text-decoration: none !important;
    transition: transform .15s ease, box-shadow .15s ease;
}

.exam-download-button:hover {
    box-shadow: 0 10px 24px rgba(37, 99, 235, .35);
    transform: translateY(-2px);
}

.exam-download-button > i {
    font-size: 25px;
    grid-row: 1 / span 2;
    text-align: center;
}

.exam-download-button > span {
    font-size: 17px;
    font-weight: 750;
}

.exam-download-button > small {
    color: #dbeafe;
    font-size: 12px;
}

.exam-download-button.disabled {
    background: #1e293b;
    border-color: #475569;
    color: #94a3b8 !important;
    cursor: not-allowed;
}

.exam-download-button.disabled small {
    color: #94a3b8;
}

.exam-download-button.disabled:hover {
    box-shadow: none;
    transform: none;
}

@media (max-width: 700px) {
    .download-grid {
        grid-template-columns: 1fr;
        padding: 18px;
    }

    .download-card-header {
        padding: 20px;
    }
}
</style>
@stop
