@extends('adminlte::page')

@section('title', 'Crear Dispositivo Guardianes del Camino')

@section('content_header')
    <h1>Captura de Dispositivo - Guardianes del Camino</h1>
@stop

@section('content')
    @php
        $dispositivosConfig = [
            'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)' => [
                'titulo' => 'PSV (Puesto de Seguridad y Vigilancia)',
                'campos' => [
                    'cantidad',
                    'vehiculos_inspeccionados',
                    'personas_inspeccionadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)' => [
                'titulo' => 'RSV (Recorridos de Seguridad y Vigilancia - Patrullaje)',
                'campos' => [
                    'cantidad',
                    'vehiculos_inspeccionados',
                    'personas_inspeccionadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'DISPOSITIVO CASCO' => [
                'titulo' => 'Dispositivo Casco',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'personas_impactadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'DISPOSITIVO CINTURON' => [
                'titulo' => 'Dispositivo Cinturón',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'personas_impactadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'DISPOSITIVO CINTURÓN' => [
                'titulo' => 'Dispositivo Cinturón',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'personas_impactadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'DISPOSITIVO CARRUSEL' => [
                'titulo' => 'Dispositivo Carrusel',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'CORDILLERA' => [
                'titulo' => 'Cordillera',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'personas_impactadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'DISPOSITIVO ASIENTO SEGURO PASAJEROS MENORES' => [
                'titulo' => 'Dispositivo Asiento Seguro Pasajeros Menores',
                'campos' => [
                    'cantidad',
                    'vehiculos_impactados',
                    'personas_impactadas',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'CABALLEROS DEL CAMINO' => [
                'titulo' => 'Caballeros del Camino',
                'campos' => [
                    'cantidad',
                    'acompanamientos',
                    'abanderamientos',
                    'auxilios_viales',
                    'estado_fuerza_participante',
                    'crps_participantes',
                    'kilometros_recorridos',
                ],
            ],
            'PROXIMIDAD SOCIAL' => [
                'titulo' => 'Proximidad Social',
                'campos' => [
                    'prox_empresas',
                    'prox_tiendas_conveniencia',
                    'prox_escuelas',
                    'prox_hospitales',
                ],
            ],
        ];

        $allCampos = [
            'cantidad',
            'vehiculos_inspeccionados',
            'personas_inspeccionadas',
            'vehiculos_impactados',
            'personas_impactadas',
            'estado_fuerza_participante',
            'crps_participantes',
            'kilometros_recorridos',
            'acompanamientos',
            'abanderamientos',
            'auxilios_viales',
            'prox_empresas',
            'prox_tiendas_conveniencia',
            'prox_escuelas',
            'prox_hospitales',
            'puestas_disposicion',
            'vehiculos_recuperados',
            'armas_aseguradas',
            'mercancia_recuperada',
            'decomiso_drogas',
            'antecedentes_personas',
            'antecedentes_vehiculos',
            'antecedentes_motos',
            'antecedentes_camiones',
        ];
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Registro diario de actividades</h3>
                </div>

                <div class="card-body">
                    <form id="form_dispositivo" action="{{ route('guardianes_camino.dispositivos.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="unidad_org_id" value="{{ old('unidad_org_id', auth()->user()->unidad_org_id ?? '') }}">

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info mb-4">
                                    <strong>Operativo:</strong> Guardianes del Camino
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="operativo_dispositivo_catalogo_id">Dispositivo <span style="color:red">*</span></label>
                                    <select name="operativo_dispositivo_catalogo_id" id="operativo_dispositivo_catalogo_id" class="form-control @error('operativo_dispositivo_catalogo_id') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('operativo_dispositivo_catalogo_id') ? '' : 'selected' }}>Seleccione un dispositivo</option>
                                        @foreach($catalogos as $catalogo)
                                            <option
                                                value="{{ $catalogo->id }}"
                                                data-nombre="{{ mb_strtoupper(trim($catalogo->nombre), 'UTF-8') }}"
                                                {{ (string) old('operativo_dispositivo_catalogo_id') === (string) $catalogo->id ? 'selected' : '' }}>
                                                {{ $catalogo->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('operativo_dispositivo_catalogo_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha">Fecha <span style="color:red">*</span></label>
                                    <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', now()->toDateString()) }}" required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hora">Hora</label>
                                    <input type="text" name="hora" id="hora" class="form-control @error('hora') is-invalid @enderror" value="{{ old('hora', now()->format('H:i')) }}" placeholder="HH:MM">
                                    @error('hora')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destacamento_id">Destacamento <span style="color:red">*</span></label>
                                    <select name="destacamento_id" id="destacamento_id" class="form-control @error('destacamento_id') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('destacamento_id') ? '' : 'selected' }}>Seleccione un destacamento</option>
                                        @if(isset($destacamentos) && $destacamentos->count())
                                            @foreach($destacamentos as $destacamento)
                                                <option value="{{ $destacamento->id }}" {{ (string) old('destacamento_id', auth()->user()->destacamento_id ?? '') === (string) $destacamento->id ? 'selected' : '' }}>
                                                    {{ $destacamento->nombre }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('destacamento_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lugar">Lugar</label>
                                    <input type="text" name="lugar" id="lugar" class="form-control @error('lugar') is-invalid @enderror" value="{{ old('lugar') }}" placeholder="Ingrese el lugar">
                                    @error('lugar')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="3" class="form-control @error('descripcion') is-invalid @enderror" placeholder="Describa brevemente la actividad">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div id="bloque_resumen_dispositivo" class="d-none">
                            <div class="alert alert-secondary mb-4">
                                <strong id="titulo_dispositivo_dinamico">Dispositivo seleccionado</strong>
                            </div>
                        </div>

                        <div id="seccion_datos_dinamicos" class="d-none">
                            <hr>

                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h5>Datos del dispositivo</h5>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="cantidad">
                                    <div class="form-group">
                                        <label for="cantidad">Cantidad</label>
                                        <input type="number" name="cantidad" id="cantidad" min="0" class="form-control @error('cantidad') is-invalid @enderror" value="{{ old('cantidad', 0) }}">
                                        @error('cantidad')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="vehiculos_inspeccionados">
                                    <div class="form-group">
                                        <label for="vehiculos_inspeccionados">Vehículos inspeccionados</label>
                                        <input type="number" name="vehiculos_inspeccionados" id="vehiculos_inspeccionados" min="0" class="form-control @error('vehiculos_inspeccionados') is-invalid @enderror" value="{{ old('vehiculos_inspeccionados', 0) }}">
                                        @error('vehiculos_inspeccionados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="personas_inspeccionadas">
                                    <div class="form-group">
                                        <label for="personas_inspeccionadas">Personas inspeccionadas</label>
                                        <input type="number" name="personas_inspeccionadas" id="personas_inspeccionadas" min="0" class="form-control @error('personas_inspeccionadas') is-invalid @enderror" value="{{ old('personas_inspeccionadas', 0) }}">
                                        @error('personas_inspeccionadas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="vehiculos_impactados">
                                    <div class="form-group">
                                        <label for="vehiculos_impactados">Vehículos impactados</label>
                                        <input type="number" name="vehiculos_impactados" id="vehiculos_impactados" min="0" class="form-control @error('vehiculos_impactados') is-invalid @enderror" value="{{ old('vehiculos_impactados', 0) }}">
                                        @error('vehiculos_impactados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="personas_impactadas">
                                    <div class="form-group">
                                        <label for="personas_impactadas">Personas impactadas</label>
                                        <input type="number" name="personas_impactadas" id="personas_impactadas" min="0" class="form-control @error('personas_impactadas') is-invalid @enderror" value="{{ old('personas_impactadas', 0) }}">
                                        @error('personas_impactadas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="estado_fuerza_participante">
                                    <div class="form-group">
                                        <label for="estado_fuerza_participante">Estado de fuerza participante</label>
                                        <input type="number" name="estado_fuerza_participante" id="estado_fuerza_participante" min="0" class="form-control @error('estado_fuerza_participante') is-invalid @enderror" value="{{ old('estado_fuerza_participante', 0) }}">
                                        @error('estado_fuerza_participante')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="kilometros_recorridos">
                                    <div class="form-group">
                                        <label for="kilometros_recorridos">Kilómetros recorridos</label>
                                        <input type="number" step="0.01" name="kilometros_recorridos" id="kilometros_recorridos" min="0" class="form-control @error('kilometros_recorridos') is-invalid @enderror" value="{{ old('kilometros_recorridos', 0) }}">
                                        @error('kilometros_recorridos')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6 campo-dinamico" data-campo="crps_participantes">
                                    <div class="form-group">
                                        <label for="crps_participantes">CRPS participantes</label>
                                        <input type="text" name="crps_participantes" id="crps_participantes" class="form-control @error('crps_participantes') is-invalid @enderror" value="{{ old('crps_participantes') }}" placeholder="Ejemplo: 25-1234 y 22-5678">
                                        @error('crps_participantes')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="acompanamientos">
                                    <div class="form-group">
                                        <label for="acompanamientos">Acompañamientos</label>
                                        <input type="number" name="acompanamientos" id="acompanamientos" min="0" class="form-control @error('acompanamientos') is-invalid @enderror" value="{{ old('acompanamientos', 0) }}">
                                        @error('acompanamientos')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="abanderamientos">
                                    <div class="form-group">
                                        <label for="abanderamientos">Abanderamientos</label>
                                        <input type="number" name="abanderamientos" id="abanderamientos" min="0" class="form-control @error('abanderamientos') is-invalid @enderror" value="{{ old('abanderamientos', 0) }}">
                                        @error('abanderamientos')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="auxilios_viales">
                                    <div class="form-group">
                                        <label for="auxilios_viales">Auxilios viales</label>
                                        <input type="number" name="auxilios_viales" id="auxilios_viales" min="0" class="form-control @error('auxilios_viales') is-invalid @enderror" value="{{ old('auxilios_viales', 0) }}">
                                        @error('auxilios_viales')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="prox_empresas">
                                    <div class="form-group">
                                        <label for="prox_empresas">Empresas</label>
                                        <input type="number" name="prox_empresas" id="prox_empresas" min="0" class="form-control @error('prox_empresas') is-invalid @enderror" value="{{ old('prox_empresas', 0) }}">
                                        @error('prox_empresas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="prox_tiendas_conveniencia">
                                    <div class="form-group">
                                        <label for="prox_tiendas_conveniencia">Tiendas de conveniencia</label>
                                        <input type="number" name="prox_tiendas_conveniencia" id="prox_tiendas_conveniencia" min="0" class="form-control @error('prox_tiendas_conveniencia') is-invalid @enderror" value="{{ old('prox_tiendas_conveniencia', 0) }}">
                                        @error('prox_tiendas_conveniencia')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="prox_escuelas">
                                    <div class="form-group">
                                        <label for="prox_escuelas">Escuelas</label>
                                        <input type="number" name="prox_escuelas" id="prox_escuelas" min="0" class="form-control @error('prox_escuelas') is-invalid @enderror" value="{{ old('prox_escuelas', 0) }}">
                                        @error('prox_escuelas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="prox_hospitales">
                                    <div class="form-group">
                                        <label for="prox_hospitales">Hospitales</label>
                                        <input type="number" name="prox_hospitales" id="prox_hospitales" min="0" class="form-control @error('prox_hospitales') is-invalid @enderror" value="{{ old('prox_hospitales', 0) }}">
                                        @error('prox_hospitales')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="seccion_generales_extra" class="d-none">
                            <hr>

                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h5>Resultados complementarios</h5>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="puestas_disposicion">
                                    <div class="form-group">
                                        <label for="puestas_disposicion">Puestas a disposición</label>
                                        <input type="number" name="puestas_disposicion" id="puestas_disposicion" min="0" class="form-control @error('puestas_disposicion') is-invalid @enderror" value="{{ old('puestas_disposicion', 0) }}">
                                        @error('puestas_disposicion')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="vehiculos_recuperados">
                                    <div class="form-group">
                                        <label for="vehiculos_recuperados">Vehículos recuperados</label>
                                        <input type="number" name="vehiculos_recuperados" id="vehiculos_recuperados" min="0" class="form-control @error('vehiculos_recuperados') is-invalid @enderror" value="{{ old('vehiculos_recuperados', 0) }}">
                                        @error('vehiculos_recuperados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="armas_aseguradas">
                                    <div class="form-group">
                                        <label for="armas_aseguradas">Armas aseguradas</label>
                                        <input type="number" name="armas_aseguradas" id="armas_aseguradas" min="0" class="form-control @error('armas_aseguradas') is-invalid @enderror" value="{{ old('armas_aseguradas', 0) }}">
                                        @error('armas_aseguradas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="mercancia_recuperada">
                                    <div class="form-group">
                                        <label for="mercancia_recuperada">Mercancía recuperada</label>
                                        <input type="number" name="mercancia_recuperada" id="mercancia_recuperada" min="0" class="form-control @error('mercancia_recuperada') is-invalid @enderror" value="{{ old('mercancia_recuperada', 0) }}">
                                        @error('mercancia_recuperada')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="decomiso_drogas">
                                    <div class="form-group">
                                        <label for="decomiso_drogas">Decomiso drogas</label>
                                        <input type="number" name="decomiso_drogas" id="decomiso_drogas" min="0" class="form-control @error('decomiso_drogas') is-invalid @enderror" value="{{ old('decomiso_drogas', 0) }}">
                                        @error('decomiso_drogas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="antecedentes_personas">
                                    <div class="form-group">
                                        <label for="antecedentes_personas">Antecedentes personas</label>
                                        <input type="number" name="antecedentes_personas" id="antecedentes_personas" min="0" class="form-control @error('antecedentes_personas') is-invalid @enderror" value="{{ old('antecedentes_personas', 0) }}">
                                        @error('antecedentes_personas')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="antecedentes_vehiculos">
                                    <div class="form-group">
                                        <label for="antecedentes_vehiculos">Antecedentes vehículos</label>
                                        <input type="number" name="antecedentes_vehiculos" id="antecedentes_vehiculos" min="0" class="form-control @error('antecedentes_vehiculos') is-invalid @enderror" value="{{ old('antecedentes_vehiculos', 0) }}">
                                        @error('antecedentes_vehiculos')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="antecedentes_motos">
                                    <div class="form-group">
                                        <label for="antecedentes_motos">Antecedentes motos</label>
                                        <input type="number" name="antecedentes_motos" id="antecedentes_motos" min="0" class="form-control @error('antecedentes_motos') is-invalid @enderror" value="{{ old('antecedentes_motos', 0) }}">
                                        @error('antecedentes_motos')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-3 campo-dinamico" data-campo="antecedentes_camiones">
                                    <div class="form-group">
                                        <label for="antecedentes_camiones">Antecedentes camiones</label>
                                        <input type="number" name="antecedentes_camiones" id="antecedentes_camiones" min="0" class="form-control @error('antecedentes_camiones') is-invalid @enderror" value="{{ old('antecedentes_camiones', 0) }}">
                                        @error('antecedentes_camiones')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="seccion_observaciones" class="d-none">
                            <hr>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observaciones">Observaciones</label>
                                        <textarea name="observaciones" id="observaciones" rows="4" class="form-control @error('observaciones') is-invalid @enderror" placeholder="Ingrese observaciones">{{ old('observaciones') }}</textarea>
                                        @error('observaciones')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group mb-0">
                                    <button id="btn_submit" type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <script id="dispositivos-config" type="application/json">
                        @json($dispositivosConfig)
                    </script>

                    <script id="all-campos-config" type="application/json">
                        @json($allCampos)
                    </script>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

        .form-control,
        select.form-control,
        textarea.form-control {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .form-control::placeholder,
        textarea.form-control::placeholder {
            color: rgba(234,240,255,.55);
        }

        select option {
            color: #111 !important;
            background-color: #ffffff !important;
        }

        select option:checked {
            background-color: #dbeafe !important;
            color: #0b1220 !important;
        }

        .form-control:focus,
        select:focus,
        textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

        .flatpickr-calendar {
            border-radius: 14px;
            overflow: hidden;
        }

        .flatpickr-time input,
        .flatpickr-time .flatpickr-am-pm {
            font-size: 16px;
        }

        .alert-info,
        .alert-secondary {
            border-radius: 14px;
        }

        .campo-oculto {
            display: none !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const horaInput = document.getElementById('hora');
            const selectDispositivo = document.getElementById('operativo_dispositivo_catalogo_id');
            const resumen = document.getElementById('bloque_resumen_dispositivo');
            const titulo = document.getElementById('titulo_dispositivo_dinamico');
            const seccionDinamica = document.getElementById('seccion_datos_dinamicos');
            const seccionExtra = document.getElementById('seccion_generales_extra');
            const seccionObservaciones = document.getElementById('seccion_observaciones');
            const config = JSON.parse(document.getElementById('dispositivos-config').textContent);
            const allCampos = JSON.parse(document.getElementById('all-campos-config').textContent);

            if (horaInput && horaInput.value) {
                horaInput.value = String(horaInput.value).substring(0, 5);
            }

            if (horaInput && window.flatpickr) {
                flatpickr(horaInput, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    allowInput: true
                });
            }

            function normalizarNombre(nombre) {
                return String(nombre || '')
                    .trim()
                    .toUpperCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '');
            }

            function obtenerClaveConfig(nombre) {
                const normalizado = normalizarNombre(nombre);

                if (config[normalizado]) {
                    return normalizado;
                }

                for (const key in config) {
                    if (normalizarNombre(key) === normalizado) {
                        return key;
                    }
                }

                return null;
            }

            function ocultarTodosLosCampos() {
                document.querySelectorAll('.campo-dinamico').forEach(item => {
                    item.classList.add('campo-oculto');
                    const input = item.querySelector('input, select, textarea');
                    if (input) {
                        input.disabled = true;
                    }
                });
            }

            function mostrarCampos(campos) {
                campos.forEach(nombreCampo => {
                    const bloque = document.querySelector('.campo-dinamico[data-campo="' + nombreCampo + '"]');
                    if (bloque) {
                        bloque.classList.remove('campo-oculto');
                        const input = bloque.querySelector('input, select, textarea');
                        if (input) {
                            input.disabled = false;
                        }
                    }
                });
            }

            function actualizarFormulario() {
                const selectedOption = selectDispositivo.options[selectDispositivo.selectedIndex];

                ocultarTodosLosCampos();
                resumen.classList.add('d-none');
                seccionDinamica.classList.add('d-none');
                seccionExtra.classList.add('d-none');
                seccionObservaciones.classList.add('d-none');

                if (!selectedOption || !selectedOption.dataset.nombre) {
                    return;
                }

                const nombre = selectedOption.dataset.nombre;
                const clave = obtenerClaveConfig(nombre);

                if (!clave || !config[clave]) {
                    return;
                }

                resumen.classList.remove('d-none');
                titulo.textContent = config[clave].titulo;
                seccionDinamica.classList.remove('d-none');
                seccionObservaciones.classList.remove('d-none');
                mostrarCampos(config[clave].campos);
            }

            if (selectDispositivo) {
                selectDispositivo.addEventListener('change', actualizarFormulario);
                actualizarFormulario();
            }
        });

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@stop
