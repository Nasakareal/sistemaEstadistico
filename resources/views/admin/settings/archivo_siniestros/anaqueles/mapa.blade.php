@extends('adminlte::page')

@section('title', 'Mapa del archivo')

@section('content_header')
    <h1>Mapa de ubicación del archivo</h1>
@stop

@section('content')
    @include('admin.settings.archivo_siniestros._nav')
    @include('admin.settings.archivo_siniestros._alerts')
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> Selecciona un bloque para conocer su capacidad, ocupación y reserva. Si capturas las coordenadas y dimensiones de los anaqueles, el plano respetará su ubicación dentro de cada sala.</div>
<div class="row">
    <div class="col-lg-9">
        @forelse($areas as $area)
            <div class="card card-outline card-primary mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fa-solid fa-door-open mr-1"></i> {{ $area->nombre }} <small class="text-muted">{{ $area->codigo }}</small></h3></div>
                <div class="card-body">
                    @php
                        $conPlano = (float) $area->plano_ancho > 0 && (float) $area->plano_alto > 0
                            && $area->anaqueles->isNotEmpty()
                            && $area->anaqueles->every(fn ($a) => $a->plano_x !== null && $a->plano_y !== null && $a->plano_ancho !== null && $a->plano_alto !== null);
                    @endphp
                    <div class="archivo-sala {{ $conPlano ? 'archivo-sala-ubicada' : 'archivo-sala-grid' }}">
                    @forelse($area->anaqueles as $anaquel)
                        @php
                            $estilo = $conPlano
                                ? 'left:'.max(0,min(100,100*(float)$anaquel->plano_x/(float)$area->plano_ancho)).'%;top:'.max(0,min(100,100*(float)$anaquel->plano_y/(float)$area->plano_alto)).'%;width:'.max(14,min(100,100*(float)$anaquel->plano_ancho/(float)$area->plano_ancho)).'%;min-height:'.max(90,min(360,360*(float)$anaquel->plano_alto/(float)$area->plano_alto)).'px;'
                                : '';
                        @endphp
                        <div class="archivo-anaquel" style="{{ $estilo }}">
                            <div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $anaquel->codigo }}</strong><small>{{ $anaquel->nombre }}</small></div>
                            <div class="archivo-cuadricula" style="grid-template-columns:repeat({{ max(1,(int)$anaquel->total_columnas) }},minmax(0,1fr))">
                                @for($fila=1;$fila<=(int)$anaquel->total_filas;$fila++)
                                    @for($columna=1;$columna<=(int)$anaquel->total_columnas;$columna++)
                                        @php
                                            $bloque = $anaquel->bloques->first(fn ($b) => (int)$b->fila === $fila && (int)$b->columna === $columna);
                                            $usados = $bloque ? (int) ($ocupaciones[$bloque->id] ?? 0) : 0;
                                            $reservados = $bloque ? (int) ($reservas[$bloque->id] ?? 0) : 0;
                                            $capacidad = $bloque ? (int) $bloque->capacidad_libros : 0;
                                        @endphp
                                        @if($bloque)
                                            <button type="button" class="archivo-bloque {{ $usados + $reservados >= $capacidad ? 'esta-lleno' : '' }}" data-id="{{ $bloque->id }}" data-anaquel="{{ $anaquel->codigo }}" data-bloque="{{ $bloque->codigo }}" data-capacidad="{{ $capacidad }}" data-usados="{{ $usados }}" data-reservados="{{ $reservados }}" data-fila="{{ $fila }}" data-columna="{{ $columna }}" data-qr="{{ $bloque->qr_uuid }}" data-url="{{ route('archivo_siniestros.bloques.edit', $bloque) }}"><strong>{{ $bloque->codigo }}</strong><small>{{ $usados }}/{{ $capacidad }}</small></button>
                                        @else
                                            <span class="archivo-bloque archivo-vacio" title="Posición no configurada">+</span>
                                        @endif
                                    @endfor
                                @endfor
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted p-5">No hay anaqueles activos en esta área.</div>
                    @endforelse
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-warning">Todavía no hay áreas activas. <a href="{{ route('archivo_siniestros.areas.index') }}">Configura una</a> antes de dibujar el mapa.</div>
        @endforelse
    </div>
    <div class="col-lg-3">
        <div class="card card-outline card-info sticky-top" style="top:1rem;z-index:2">
            <div class="card-header"><h3 class="card-title">Bloque seleccionado</h3></div>
            <div class="card-body">
                <h3 id="archivo-bloque-nombre">Selecciona un bloque</h3>
                <p id="archivo-bloque-ubicacion" class="text-muted">Pulsa un cuadro del mapa.</p>
                <div class="d-flex justify-content-between"><span>Ocupación confirmada</span><strong id="archivo-ocupacion">—</strong></div>
                <div class="d-flex justify-content-between"><span>Reservas pendientes</span><strong id="archivo-reservas">—</strong></div>
                <div class="d-flex justify-content-between"><span>Capacidad</span><strong id="archivo-capacidad">—</strong></div>
                <div class="progress my-3"><div id="archivo-progreso" class="progress-bar bg-info" style="width:0%"></div></div>
                <div><small class="text-muted">QR del bloque</small><br><code id="archivo-qr" class="text-break">—</code></div>
                @can('gestionar archivo siniestros')<a id="archivo-editar" class="btn btn-info btn-block mt-3 disabled" href="#" aria-disabled="true">Editar bloque</a>@endcan
            </div>
        </div>
    </div>
</div>
@stop

@section('css')

<style>
.archivo-sala{border:2px solid #8a98ab;border-radius:10px;padding:18px;background:rgba(110,140,170,.08);min-height:170px;gap:16px}
.archivo-sala-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
.archivo-sala-ubicada{position:relative;height:480px;overflow:auto}
.archivo-sala-ubicada .archivo-anaquel{position:absolute;overflow:auto}
.archivo-anaquel{border:1px solid #667b91;background:rgba(110,140,170,.12);border-radius:8px;padding:12px;min-width:160px}
.archivo-cuadricula{display:grid;gap:5px}
.archivo-bloque{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:54px;min-width:0;padding:4px 2px;border:1px solid #4c7898;background:rgba(23,162,184,.13);color:inherit;border-radius:6px;cursor:pointer}
.archivo-bloque small{font-size:10px}
.archivo-bloque.esta-lleno{border-color:#c68934;background:rgba(230,170,50,.18)}
.archivo-bloque.seleccionado{outline:3px solid #20c997;outline-offset:1px}
.archivo-vacio{opacity:.4;cursor:default;border-style:dashed}
@media(max-width:767px){.archivo-sala-ubicada{height:auto;display:grid;grid-template-columns:1fr}.archivo-sala-ubicada .archivo-anaquel{position:static;min-height:initial!important;width:100%!important}}
</style>

@stop

@section('js')

<script>
document.querySelectorAll('.archivo-bloque[data-id]').forEach(function(boton){
    boton.addEventListener('click',function(){
        document.querySelectorAll('.archivo-bloque.seleccionado').forEach(function(x){x.classList.remove('seleccionado')});
        boton.classList.add('seleccionado');
        var d=boton.dataset, ocupados=Number(d.usados), reservas=Number(d.reservados), capacidad=Number(d.capacidad);
        document.getElementById('archivo-bloque-nombre').textContent=d.anaquel+' / '+d.bloque;
        document.getElementById('archivo-bloque-ubicacion').textContent='Fila '+d.fila+' · Columna '+d.columna;
        document.getElementById('archivo-ocupacion').textContent=ocupados;
        document.getElementById('archivo-reservas').textContent=reservas;
        document.getElementById('archivo-capacidad').textContent=capacidad;
        document.getElementById('archivo-qr').textContent=d.qr||'Sin QR';
        document.getElementById('archivo-progreso').style.width=(capacidad?Math.min(100,(ocupados+reservas)*100/capacidad):0)+'%';
        var editar=document.getElementById('archivo-editar');
        if(editar){editar.href=d.url;editar.classList.remove('disabled');editar.removeAttribute('aria-disabled')}
    });
});
var id=new URLSearchParams(window.location.search).get('bloque_id');
if(id){var objetivo=Array.from(document.querySelectorAll('.archivo-bloque[data-id]')).find(function(el){return el.dataset.id===id});if(objetivo){objetivo.click();objetivo.scrollIntoView({block:'center',behavior:'smooth'})}}
</script>

@stop
