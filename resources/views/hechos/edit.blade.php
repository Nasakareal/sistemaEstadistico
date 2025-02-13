@extends('adminlte::page')

@section('title', 'Editar Hecho de Tránsito')

@section('content_header')
    <h1>Edición de Hecho de Tránsito</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <!-- Usamos card-success para indicar que es edición (verde) -->
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Edite los Datos</h3>
                </div>
                <div class="card-body">
                    <!-- La acción del formulario apunta a la ruta de actualización con el id del hecho -->
                    <form action="{{ route('hechos.update', $hecho->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- FILA 1: Folio de C5i, Perito, N° Autorización de Práctico, Unidad -->
                        <div class="row">
                            <!-- Folio de C5i -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="folio_c5i">Folio de C5i</label>
                                    <input type="text" name="folio_c5i" id="folio_c5i" 
                                           class="form-control @error('folio_c5i') is-invalid @enderror" 
                                           value="{{ old('folio_c5i', $hecho->folio_c5i) }}" 
                                           placeholder="Ingrese el folio de C5i" required>
                                    @error('folio_c5i')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Perito -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="perito">Perito</label>
                                    <input type="text" name="perito" id="perito" 
                                           class="form-control @error('perito') is-invalid @enderror" 
                                           value="{{ old('perito', $hecho->perito) }}" 
                                           placeholder="Nombre del perito" required>
                                    @error('perito')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- N° Autorización de Práctico -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="autorizacion_practico">N° Autorización de Práctico</label>
                                    <input type="text" name="autorizacion_practico" id="autorizacion_practico" 
                                           class="form-control @error('autorizacion_practico') is-invalid @enderror" 
                                           value="{{ old('autorizacion_practico', $hecho->autorizacion_practico) }}" 
                                           placeholder="Ingrese el número de autorización" required>
                                    @error('autorizacion_practico')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Unidad -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="unidad">Unidad</label>
                                    <input type="text" name="unidad" id="unidad" 
                                           class="form-control @error('unidad') is-invalid @enderror" 
                                           value="{{ old('unidad', $hecho->unidad) }}" 
                                           placeholder="Ingrese la unidad" required>
                                    @error('unidad')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 2: Hora, Fecha, Sector, Municipio -->
                        <div class="row">
                            <!-- Hora -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora">Hora</label>
                                    <input type="time" name="hora" id="hora" 
                                           class="form-control @error('hora') is-invalid @enderror" 
                                           value="{{ old('hora', $hecho->hora) }}" required>
                                    @error('hora')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Fecha -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input type="date" name="fecha" id="fecha" 
                                           class="form-control @error('fecha') is-invalid @enderror" 
                                           value="{{ old('fecha', $hecho->fecha) }}" required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Sector -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sector">Sector</label>
                                    <select name="sector" id="sector" 
                                            class="form-control @error('sector') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione un sector</option>
                                        <option value="REVOLUCIÓN" {{ old('sector', $hecho->sector) == 'REVOLUCIÓN' ? 'selected' : '' }}>REVOLUCIÓN</option>
                                        <option value="NUEVA ESPAÑA" {{ old('sector', $hecho->sector) == 'NUEVA ESPAÑA' ? 'selected' : '' }}>NUEVA ESPAÑA</option>
                                        <option value="INDEPENDENCIA" {{ old('sector', $hecho->sector) == 'INDEPENDENCIA' ? 'selected' : '' }}>INDEPENDENCIA</option>
                                        <option value="REPÚBLICA" {{ old('sector', $hecho->sector) == 'REPÚBLICA' ? 'selected' : '' }}>REPÚBLICA</option>
                                        <option value="CENTRO" {{ old('sector', $hecho->sector) == 'CENTRO' ? 'selected' : '' }}>CENTRO</option>
                                    </select>
                                    @error('sector')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Municipio -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="municipio">Municipio</label>
                                    <input type="text" name="municipio" id="municipio" 
                                           class="form-control @error('municipio') is-invalid @enderror" 
                                           value="{{ old('municipio', $hecho->municipio) }}" 
                                           placeholder="Ingrese el municipio" required>
                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 3: Calle, Colonia, Entre Calles -->
                        <div class="row">
                            <!-- Calle -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="calle">Calle</label>
                                    <input type="text" name="calle" id="calle" 
                                           class="form-control @error('calle') is-invalid @enderror" 
                                           value="{{ old('calle', $hecho->calle) }}" 
                                           placeholder="Ingrese la calle" required>
                                    @error('calle')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Colonia -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colonia">Colonia</label>
                                    <input type="text" name="colonia" id="colonia" 
                                           class="form-control @error('colonia') is-invalid @enderror" 
                                           value="{{ old('colonia', $hecho->colonia) }}" 
                                           placeholder="Ingrese la colonia" required>
                                    @error('colonia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Entre Calles -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="entre_calles">Entre Calles</label>
                                    <input type="text" name="entre_calles" id="entre_calles" 
                                           class="form-control @error('entre_calles') is-invalid @enderror" 
                                           value="{{ old('entre_calles', $hecho->entre_calles) }}" 
                                           placeholder="Ingrese entre calles">
                                    @error('entre_calles')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 4: Tipo de Hecho, Superficie de la Vía, Tiempo, Clima y Condiciones -->
                        <div class="row">
                            <!-- Tipo de Hecho de Tránsito -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_hecho">Tipo de Hecho de Tránsito</label>
                                    <select name="tipo_hecho" id="tipo_hecho" 
                                            class="form-control @error('tipo_hecho') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el tipo de hecho</option>
                                        <option value="VOLCADURA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'VOLCADURA' ? 'selected' : '' }}>VOLCADURA</option>
                                        <option value="SALIDA DE SUPERFICIE DE RODAMIENTO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'SALIDA DE SUPERFICIE DE RODAMIENTO' ? 'selected' : '' }}>SALIDA DE SUPERFICIE DE RODAMIENTO</option>
                                        <option value="SUBIDA AL CAMELLÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'SUBIDA AL CAMELLÓN' ? 'selected' : '' }}>SUBIDA AL CAMELLÓN</option>
                                        <option value="CAIDA DE MOTOCICLETA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'CAIDA DE MOTOCICLETA' ? 'selected' : '' }}>CAIDA DE MOTOCICLETA</option>
                                        <option value="COLISIÓN CON PEATÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN CON PEATÓN' ? 'selected' : '' }}>COLISIÓN CON PEATÓN</option>
                                        <option value="COLISIÓN POR ALCANCE" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR ALCANCE' ? 'selected' : '' }}>COLISIÓN POR ALCANCE</option>
                                        <option value="COLISIÓN POR NO RESPETAR SEMÁFORO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR NO RESPETAR SEMÁFORO' ? 'selected' : '' }}>COLISIÓN POR NO RESPETAR SEMÁFORO</option>
                                        <option value="COLISIÓN POR INVASIÓN DE CARRIL" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR INVASIÓN DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR INVASIÓN DE CARRIL</option>
                                        <option value="COLISIÓN POR CAMBIO DE CARRIL" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR CAMBIO DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR CAMBIO DE CARRIL</option>
                                        <option value="COLISIÓN POR CORTE DE CIRCULACIÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR CORTE DE CIRCULACIÓN' ? 'selected' : '' }}>COLISIÓN POR CORTE DE CIRCULACIÓN</option>
                                        <option value="COLISIÓN POR MANIOBRA DE REVERSA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR MANIOBRA DE REVERSA' ? 'selected' : '' }}>COLISIÓN POR MANIOBRA DE REVERSA</option>
                                        <option value="CAIDA ACUATICA DE VEHÍCULO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'CAIDA ACUATICA DE VEHÍCULO' ? 'selected' : '' }}>CAIDA ACUATICA DE VEHÍCULO</option>
                                        <option value="DESBARRANCAMIENTO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'DESBARRANCAMIENTO' ? 'selected' : '' }}>DESBARRANCAMIENTO</option>
                                        <option value="INCENDIO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'INCENDIO' ? 'selected' : '' }}>INCENDIO</option>
                                        <option value="EXPLOSIÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'EXPLOSIÓN' ? 'selected' : '' }}>EXPLOSIÓN</option>
                                        <option value="Otro" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'Otro' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('tipo_hecho')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Superficie de la Vía -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="superficie_via">Superficie de la Vía</label>
                                    <input type="text" name="superficie_via" id="superficie_via" 
                                           class="form-control @error('superficie_via') is-invalid @enderror" 
                                           value="{{ old('superficie_via', $hecho->superficie_via) }}" 
                                           placeholder="Ingrese la superficie de la vía" required>
                                    @error('superficie_via')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Tiempo -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tiempo">Tiempo</label>
                                    <select name="tiempo" id="tiempo" 
                                            class="form-control @error('tiempo') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el tiempo</option>
                                        <option value="Día" {{ old('tiempo', $hecho->tiempo) == 'Día' ? 'selected' : '' }}>DÍA</option>
                                        <option value="Noche" {{ old('tiempo', $hecho->tiempo) == 'Noche' ? 'selected' : '' }}>NOCHE</option>
                                        <option value="Amanecer" {{ old('tiempo', $hecho->tiempo) == 'Amanecer' ? 'selected' : '' }}>AMANECER</option>
                                        <option value="Atardecer" {{ old('tiempo', $hecho->tiempo) == 'Atardecer' ? 'selected' : '' }}>ATARDECER</option>
                                    </select>
                                    @error('tiempo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Clima -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="clima">Clima</label>
                                    <select name="clima" id="clima" 
                                            class="form-control @error('clima') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el clima</option>
                                        <option value="Bueno" {{ old('clima', $hecho->clima) == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Malo" {{ old('clima', $hecho->clima) == 'Malo' ? 'selected' : '' }}>MALO</option>
                                        <option value="Nublado" {{ old('clima', $hecho->clima) == 'Nublado' ? 'selected' : '' }}>NUBLADO</option>
                                        <option value="Lluvioso" {{ old('clima', $hecho->clima) == 'Lluvioso' ? 'selected' : '' }}>LLUVIOSO</option>
                                    </select>
                                    @error('clima')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Condiciones -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="condiciones">Condiciones</label>
                                    <select name="condiciones" id="condiciones" 
                                            class="form-control @error('condiciones') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione las condiciones</option>
                                        <option value="Bueno" {{ old('condiciones', $hecho->condiciones) == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Regular" {{ old('condiciones', $hecho->condiciones) == 'Regular' ? 'selected' : '' }}>REGULAR</option>
                                        <option value="Malo" {{ old('condiciones', $hecho->condiciones) == 'Malo' ? 'selected' : '' }}>MALO</option>
                                    </select>
                                    @error('condiciones')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 5: Situación, Control de Tránsito, Colisión sobre el Camino -->
                        <div class="row">
                            <!-- Situación -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="situacion">Situación</label>
                                    <select name="situacion" id="situacion" 
                                            class="form-control @error('situacion') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione la situación</option>
                                        <option value="RESUELTO" {{ old('situacion', $hecho->situacion) == 'RESUELTO' ? 'selected' : '' }}>RESUELTO</option>
                                        <option value="PENDIENTE" {{ old('situacion', $hecho->situacion) == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                                        <option value="TURNADO" {{ old('situacion', $hecho->situacion) == 'TURNADO' ? 'selected' : '' }}>TURNADO</option>
                                        <option value="REPORTE" {{ old('situacion', $hecho->situacion) == 'REPORTE' ? 'selected' : '' }}>REPORTE</option>
                                    </select>
                                    @error('situacion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Control de Tránsito -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="control_transito">Control de Tránsito</label>
                                    <input type="text" name="control_transito" id="control_transito" 
                                           class="form-control @error('control_transito') is-invalid @enderror" 
                                           value="{{ old('control_transito', $hecho->control_transito) }}" 
                                           placeholder="Ingrese el control de tránsito" required>
                                    @error('control_transito')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Colisión sobre el Camino -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colision_camino">Colisión sobre el Camino</label>
                                    <input type="text" name="colision_camino" id="colision_camino" 
                                           class="form-control @error('colision_camino') is-invalid @enderror" 
                                           value="{{ old('colision_camino', $hecho->colision_camino) }}" 
                                           placeholder="Ingrese la colisión sobre el camino" required>
                                    @error('colision_camino')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 6: Se checaron antecedentes?, Causas -->
                        <div class="row">
                            <!-- Se checaron antecedentes? -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="checaron_antecedentes">Se checaron antecedentes?</label>
                                    <select name="checaron_antecedentes" id="checaron_antecedentes" class="form-control">
                                        <option value="0" {{ old('checaron_antecedentes', $hecho->checaron_antecedentes) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('checaron_antecedentes', $hecho->checaron_antecedentes) == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Causas -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="causas">Causas</label>
                                    <input type="text" name="causas" id="causas" 
                                           class="form-control @error('causas') is-invalid @enderror" 
                                           value="{{ old('causas', $hecho->causas) }}" 
                                           placeholder="Ingrese las causas" required>
                                    @error('causas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- FILA 7: Oficio MP (sólo si situación es TURNADO), Vehículos y Personas presentados al MP -->
                        <div class="row">
                            <!-- Oficio MP (Visible solo si situación es TURNADO) -->
                            <div class="col-md-4" id="oficio_mp_group" style="display: none;">
                                <div class="form-group">
                                    <label for="oficio_mp">Oficio MP</label>
                                    <input type="text" name="oficio_mp" id="oficio_mp" 
                                           class="form-control @error('oficio_mp') is-invalid @enderror" 
                                           value="{{ old('oficio_mp', $hecho->oficio_mp) }}" 
                                           placeholder="Ingrese el número de oficio">
                                    @error('oficio_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Vehículos presentados al MP -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="vehiculos_mp">Vehículos presentados al MP</label>
                                    <input type="number" name="vehiculos_mp" id="vehiculos_mp" 
                                           class="form-control @error('vehiculos_mp') is-invalid @enderror" 
                                           value="{{ old('vehiculos_mp', $hecho->vehiculos_mp) }}" 
                                           min="0" required>
                                    @error('vehiculos_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Personas presentadas al MP -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="personas_mp">Personas presentadas al MP</label>
                                    <input type="number" name="personas_mp" id="personas_mp" 
                                           class="form-control @error('personas_mp') is-invalid @enderror" 
                                           value="{{ old('personas_mp', $hecho->personas_mp) }}" 
                                           min="0" required>
                                    @error('personas_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <a href="{{ route('vehiculos.index', $hecho->id) }}" class="btn btn-success btn-lg w-100">
                                    <i class="fa-solid fa-car-side"></i> Listado de Vehículos
                                </a>
                            </div>


                        </div>
                        
                        <!-- BOTONES: Actualizar y Cancelar -->
                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <!-- Botón de actualizar en color success (verde) -->
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-check"></i> Actualizar
                                    </button>

                                    <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        .form-group label {
            font-weight: bold;
        }
    </style>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const situacionSelect = document.getElementById('situacion');
            const oficioMpGroup = document.getElementById('oficio_mp_group');

            function toggleOficioMp() {
                if (situacionSelect.value === 'TURNADO') {
                    oficioMpGroup.style.display = 'block';
                } else {
                    oficioMpGroup.style.display = 'none';
                    document.getElementById('oficio_mp').value = '';
                }
            }

            // Inicializar al cargar la página
            toggleOficioMp();

            // Escuchar cambios en el select de situación
            situacionSelect.addEventListener('change', toggleOficioMp);
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
