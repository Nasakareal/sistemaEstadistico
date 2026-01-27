@extends('adminlte::page')

@section('title', 'Agregar Vehículo al Hecho')

@section('content_header')
    <h1>Agregar Vehículo al Hecho: {{ $hecho->folio_c5i }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Datos del Vehículo</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('vehiculos.store', $hecho->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                        <!-- Folio de Marca, Modelo, Tipo, Línea -->
                            <div class="col-md-3">
                                <!-- Marca -->
                                <div class="form-group">
                                    <label for="marca">Marca<span style="color: red">*</span></label>
                                    <input type="text" name="marca" id="marca" class="form-control @error('marca') is-invalid @enderror" 
                                           value="{{ old('marca') }}" placeholder="Ingrese la marca" required>
                                    @error('marca')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                             </div>

                             <!-- Modelo -->
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="modelo">Modelo</label>
                                    <input type="text" name="modelo" id="modelo" class="form-control @error('modelo') is-invalid @enderror" 
                                           value="{{ old('modelo') }}" placeholder="Ingrese el modelo">
                                    @error('modelo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                             </div>

                             <!-- Tipo de Vehículo -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_general">Tipo de Vehículo<span style="color: red">*</span></label>
                                    <select name="tipo_general" id="tipo_general" class="form-control" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="semoviente" {{ old('tipo_general') == 'semoviente' ? 'selected' : '' }}>Semoviente</option>
                                        <option value="automovil" {{ old('tipo_general') == 'automovil' ? 'selected' : '' }}>Automóvil</option>
                                        <option value="camion" {{ old('tipo_general') == 'camion' ? 'selected' : '' }}>Camión</option>
                                        <option value="camioneta" {{ old('tipo_general') == 'camioneta' ? 'selected' : '' }}>Camioneta</option>
                                        <option value="bicicleta" {{ old('tipo_general') == 'bicicleta' ? 'selected' : '' }}>Bicicleta</option>
                                        <option value="motocicleta" {{ old('tipo_general') == 'motocicleta' ? 'selected' : '' }}>Motocicleta</option>
                                        <option value="remolque" {{ old('tipo_general') == 'remolque' ? 'selected' : '' }}>Remolque</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Carrocería -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo">Carrocería<span style="color: red">*</span></label>
                                    <select name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror" required>
                                        <option value="">-- Seleccione un tipo general primero --</option>
                                    </select>
                                    @error('tipo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>                            
                         </div>

                         
                        <!-- Fila para aseguradora -->
                        <div class="row">
                            <!-- Línea -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="linea">Línea<span style="color: red">*</span></label>
                                    <input type="text" name="linea" id="linea" class="form-control @error('linea') is-invalid @enderror" 
                                            value="{{ old('linea') }}" placeholder="Ingrese la línea" required>
                                    @error('linea')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <!-- Aseguradora -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="aseguradora">Aseguradora</label>
                                    <input type="text" name="aseguradora" id="aseguradora"
                                       class="form-control @error('aseguradora') is-invalid @enderror"
                                       value="{{ old('aseguradora') }}"
                                       placeholder="Nombre de la aseguradora">
                                    @error('aseguradora')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                         <!-- Color, Placas, Estado de Placas -->
                         <div class="row">
                            <!-- Color -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="color">Color<span style="color: red">*</span></label>
                                    <input type="text" name="color" id="color" class="form-control @error('color') is-invalid @enderror" 
                                           value="{{ old('color') }}" placeholder="Ingrese el color" required>
                                    @error('color')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Placas -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="placas">Placas<span style="color: red">*</span></label>
                                    <input type="text" name="placas" id="placas" class="form-control @error('placas') is-invalid @enderror" 
                                           value="{{ old('placas') }}" placeholder="Ingrese las placas" required>
                                    @error('placas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Estado de Placas -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="estado_placas">Estado de Placas</label>
                                    <input type="text" name="estado_placas" id="estado_placas" class="form-control @error('estado_placas') is-invalid @enderror" 
                                           value="{{ old('estado_placas') }}" placeholder="Ingrese el estado de placas">
                                    @error('estado_placas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                         </div>

                        <!-- Serie, Capacidad -->
                        <div class="row">
                            <!-- Serie -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="serie">Serie</label>
                                    <input type="text" name="serie" id="serie" class="form-control @error('serie') is-invalid @enderror" 
                                           value="{{ old('serie') }}" placeholder="Ingrese la serie">
                                    @error('serie')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        
                            <!-- Capacidad de Personas -->
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="capacidad_personas">Capacidad<span style="color: red">*</span></label>
                                    <input type="number" name="capacidad_personas" id="capacidad_personas" class="form-control @error('capacidad_personas') is-invalid @enderror" 
                                           value="{{ old('capacidad_personas', 0) }}" min="0" required>
                                    @error('capacidad_personas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Tipo de Servicio -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="tipo_servicio">Tipo de Servicio<span style="color: red">*</span></label>
                                    <select name="tipo_servicio" id="tipo_servicio" class="form-control @error('tipo_servicio') is-invalid @enderror" required>
                                        <option value="">-- Seleccione --</option>
                                        <option value="Particular" {{ old('tipo_servicio') == 'Particular' ? 'selected' : '' }}>Particular</option>
                                        <option value="Oficial" {{ old('tipo_servicio') == 'Oficial' ? 'selected' : '' }}>Oficial</option>
                                        <option value="Público" {{ old('tipo_servicio') == 'Público' ? 'selected' : '' }}>Público</option>
                                    </select>
                                    @error('tipo_servicio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta, Conductor, Telefono -->
                        <div class="row">
                            <!-- Tarjeta de Circulación Nombre -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="tarjeta_circulacion_nombre">Tarjeta de Circulación Nombre</label>
                                    <input type="text" name="tarjeta_circulacion_nombre" id="tarjeta_circulacion_nombre" class="form-control @error('tarjeta_circulacion_nombre') is-invalid @enderror" 
                                           value="{{ old('tarjeta_circulacion_nombre') }}" placeholder="Ingrese el nombre de la tarjeta de circulación">
                                    @error('tarjeta_circulacion_nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Conductor -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="conductor_nombre">Nombre del Conductor</label>
                                    <input type="text" name="conductor_nombre" id="conductor_nombre"
                                           class="form-control @error('conductor_nombre') is-invalid @enderror"
                                           value="{{ old('conductor_nombre') }}" placeholder="Ingrese el nombre del conductor">
                                    @error('conductor_nombre')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="telefono">Teléfono</label>
                                    <input type="tel" name="telefono" id="telefono"
                                           class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono') }}" placeholder="Ingrese el teléfono del conductor" pattern="[0-9]{10}">
                                    <small class="form-text text-muted">Formato: 10 dígitos</small>
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Domicilio, Sexo, Ocupación -->
                        <div class="row">
                            <!-- Domicilio -->
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="domicilio">Domicilio</label>
                                    <input type="text" name="domicilio" id="domicilio"
                                           class="form-control @error('domicilio') is-invalid @enderror"
                                           value="{{ old('domicilio') }}" placeholder="Ingrese el domicilio del conductor">
                                    @error('domicilio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sexo -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sexo">Sexo</label>
                                    <select name="sexo" id="sexo" class="form-control @error('sexo') is-invalid @enderror">
                                        <option value="" disabled selected>Seleccione el sexo</option>
                                        <option value="MASCULINO" {{ old('sexo') == 'MASCULINO' ? 'selected' : '' }}>Masculino</option>
                                        <option value="FEMENINO" {{ old('sexo') == 'FEMENINO' ? 'selected' : '' }}>Femenino</option>
                                        <option value="OTRO" {{ old('sexo') == 'OTRO' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('sexo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Ocupación -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ocupacion">Ocupación</label>
                                    <input type="text" name="ocupacion" id="ocupacion"
                                           class="form-control @error('ocupacion') is-invalid @enderror"
                                           value="{{ old('ocupacion') }}" placeholder="Ingrese la ocupación del conductor">
                                    @error('ocupacion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>


                        <!-- Edad, Tipo de Licencia, Estado de Licencia, Vigencia de Licencia -->
                        <div class="row">
                            <!-- Edad -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="edad">Edad</label>
                                    <input type="number" name="edad" id="edad"
                                           class="form-control @error('edad') is-invalid @enderror"
                                           value="{{ old('edad') }}" placeholder="Ingrese la edad del conductor" min="00" max="100">
                                    @error('edad')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tipo de Licencia -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_licencia">Tipo de Licencia</label>
                                    <input type="text" name="tipo_licencia" id="tipo_licencia"
                                           class="form-control @error('tipo_licencia') is-invalid @enderror"
                                           value="{{ old('tipo_licencia') }}" placeholder="Ingrese el tipo de licencia">
                                    @error('tipo_licencia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Estado de Licencia -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estado_licencia">Estado de Licencia</label>
                                    <input type="text" name="estado_licencia" id="estado_licencia"
                                           class="form-control @error('estado_licencia') is-invalid @enderror"
                                           value="{{ old('estado_licencia') }}" placeholder="Ingrese el estado de la licencia">
                                    @error('estado_licencia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Vigencia de Licencia -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vigencia_licencia">Vigencia de Licencia</label>
                                    <input type="date" name="vigencia_licencia" id="vigencia_licencia"
                                           class="form-control @error('vigencia_licencia') is-invalid @enderror"
                                           value="{{ old('vigencia_licencia') }}">
                                    @error('vigencia_licencia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Número de Licencia -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="numero_licencia">Número de Licencia</label>
                                    <input type="text" name="numero_licencia" id="numero_licencia"
                                           class="form-control @error('numero_licencia') is-invalid @enderror"
                                           value="{{ old('numero_licencia') }}" placeholder="Ingrese el número de licencia">
                                    @error('numero_licencia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Licencia Permanente -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>
                                        <input type="hidden" name="permanente" value="0">
                                        <input type="checkbox" name="permanente" value="1"
                                               {{ old('permanente', $conductor->permanente ?? 0) ? 'checked' : '' }}>
                                        Licencia Permanente
                                    </label>

                                    @error('permanente')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                        </div>


                            
                        <!-- Partes Dañadas, Monto de los Daños, Grúa, Corralón -->
                        <div class="row">
                            <!-- Partes Dañadas -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="partes_danadas">Partes Dañadas<span style="color: red">*</span></label>
                                    <textarea name="partes_danadas" id="partes_danadas"
                                              class="form-control @error('partes_danadas') is-invalid @enderror"
                                              placeholder="Describa las partes dañadas del vehículo" required>{{ old('partes_danadas') }}</textarea>
                                    @error('partes_danadas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Monto de los Daños -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="monto_danos">Monto de los Daños<span style="color: red">*</span></label>
                                    <input type="number" name="monto_danos" id="monto_danos"
                                           class="form-control @error('monto_danos') is-invalid @enderror"
                                           value="{{ old('monto_danos') }}" placeholder="Ingrese el monto estimado" min="0" step="0.01" required>
                                    @error('monto_danos')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Grúa -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="grua_id">Grúa</label>
                                    <select name="grua_id" id="grua_id" class="form-control @error('grua_id') is-invalid @enderror">
                                        <option value="">Seleccione una grúa</option>
                                        @foreach ($gruas as $grua)
                                            <option value="{{ $grua->id }}" 
                                                {{ old('grua_id') == $grua->id ? 'selected' : '' }}>
                                                {{ $grua->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('grua_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Corralón -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="corralon">Corralón</label>
                                    <select name="corralon" id="corralon"
                                            class="form-control @error('corralon') is-invalid @enderror">
                                        <option value="">Seleccione un corralón</option>
                                        <option value="ESTRELLA 1" {{ old('corralon') == 'ESTRELLA 1' ? 'selected' : '' }}>ESTRELLA 1</option>
                                        <option value="ESTRELLA 2" {{ old('corralon') == 'ESTRELLA 2' ? 'selected' : '' }}>ESTRELLA 2</option>
                                        <option value="AUTOPISTA" {{ old('corralon') == 'AUTOPISTA' ? 'selected' : '' }}>AUTOPISTA</option>
                                        <option value="DANNYS" {{ old('corralon') == 'DANNYS' ? 'selected' : '' }}>DANNYS</option>
                                        <option value="EXPRESS" {{ old('corralon') == 'EXPRESS' ? 'selected' : '' }}>EXPRESS</option>
                                        <option value="GALVAN" {{ old('corralon') == 'GALVAN' ? 'selected' : '' }}>GALVAN</option>
                                        <option value="HERNANDEZ" {{ old('corralon') == 'HERNANDEZ' ? 'selected' : '' }}>HERNANDEZ</option>
                                        <option value="PINEDA" {{ old('corralon') == 'PINEDA' ? 'selected' : '' }}>PINEDA</option>
                                        <option value="PROFESIONALES" {{ old('corralon') == 'PROFESIONALES' ? 'selected' : '' }}>PROFESIONALES</option>
                                        <option value="MORELIA" {{ old('corralon') == 'MORELIA' ? 'selected' : '' }}>MORELIA</option>
                                        <option value="MONARCAS" {{ old('corralon') == 'MONARCAS' ? 'selected' : '' }}>MONARCAS</option>
                                        <option value="EXPRESS" {{ old('corralon') == 'EXPRESS' ? 'selected' : '' }}>EXPRESS</option>
                                        <option value="MUÑOZ" {{ old('corralon') == 'MUÑOZ' ? 'selected' : '' }}>MUÑOZ</option>
                                    </select>
                                    @error('corralon')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                         <!-- Daños Patrimoniales, Propiedad, Monto de Daños -->
                        <div class="row">
                            <!-- Daños Patrimoniales -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="danos_patrimoniales">Daños Patrimoniales</label>
                                    <textarea name="danos_patrimoniales" id="danos_patrimoniales"
                                              class="form-control @error('danos_patrimoniales') is-invalid @enderror"
                                              placeholder="Describa los daños patrimoniales">{{ old('danos_patrimoniales') }}</textarea>
                                    @error('danos_patrimoniales')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Propiedad -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="propiedad">Propiedad</label>
                                    <input type="text" name="propiedad" id="propiedad"
                                           class="form-control @error('propiedad') is-invalid @enderror"
                                           value="{{ old('propiedad') }}" placeholder="Ingrese el nombre del propietario afectado">
                                    @error('propiedad')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Monto de Daños -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="monto_danos_patrimoniales">Monto de los Daños</label>
                                    <input type="number" name="monto_danos_patrimoniales" id="monto_danos_patrimoniales"
                                           class="form-control @error('monto_danos_patrimoniales') is-invalid @enderror"
                                           value="{{ old('monto_danos_patrimoniales') }}" placeholder="Ingrese el monto estimado" min="0" step="0.01">
                                    @error('monto_danos_patrimoniales')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Verificaciones, condición física y aseguradora -->
                        <div class="row">
                            <!-- Antecedentes del vehículo -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="antecedente_vehiculo" value="1" {{ old('antecedente_vehiculo') ? 'checked' : '' }}>
                                        ¿Antecedente vehicular?
                                    </label>
                                </div>
                            </div>

                            <!-- Antecedentes del conductor -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="antecedente_conductor" value="1" {{ old('antecedente_conductor') ? 'checked' : '' }}>
                                        ¿Antecedente conductor?
                                    </label>
                                </div>
                            </div>

                            <!-- Cinturón -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="cinturon" value="1" {{ old('cinturon') ? 'checked' : '' }}>
                                        ¿Usaba cinturón?
                                    </label>
                                </div>
                            </div>

                            <!-- Certificado de lesiones -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="certificado_lesiones" value="1" {{ old('certificado_lesiones') ? 'checked' : '' }}>
                                        Cert. lesiones
                                    </label>
                                </div>
                            </div>

                            <!-- Certificado de alcoholemia -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="certificado_alcoholemia" value="1" {{ old('certificado_alcoholemia') ? 'checked' : '' }}>
                                        Cert. alcoholemia
                                    </label>
                                </div>
                            </div>

                            <!-- Aliento etílico -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="aliento_etilico" value="1" {{ old('aliento_etilico') ? 'checked' : '' }}>
                                        Aliento etílico
                                    </label>
                                </div>
                            </div>
                        </div>

                        
                        <!-- Fotos 
                        <div class="row">
                            // Fotos 
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fotos">Fotos</label>
                                    <input type="file" name="fotos" id="fotos" class="form-control @error('fotos') is-invalid @enderror" accept="image/*">
                                    @error('fotos')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        -->
                        

                        <!-- Botones -->
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-check"></i> Registrar
                            </button>
                            <a href="{{ route('hechos.show', $hecho->id) }}" class="btn btn-secondary">
                                <i class="fa-solid fa-ban"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* ===== Labels ===== */
    .form-group label {
        font-weight: bold;
        color: #eaf0ff;
    }

    /* ===== Inputs & Selects (AdminLTE dark) ===== */
    .form-control,
    select.form-control {
        color: #eaf0ff;
        background-color: rgba(255,255,255,.06);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 12px;
    }

    /* ===== Placeholder ===== */
    .form-control::placeholder {
        color: rgba(234,240,255,.55);
    }

    /* ===== Dropdown options ===== */
    select option {
        color: #111 !important;
        background-color: #ffffff !important;
    }

    select optgroup {
        color: #111 !important;
        background-color: #ffffff !important;
        font-weight: bold;
    }

    /* ===== Selected / hover ===== */
    select option:checked {
        background-color: #dbeafe !important;
        color: #0b1220 !important;
    }

    select option:hover {
        background-color: #bfdbfe !important;
        color: #0b1220 !important;
    }

    /* ===== Focus ===== */
    .form-control:focus,
    select:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(45,168,255,.35);
        border-color: rgba(45,168,255,.55);
    }
</style>
@stop


@section('js')
    <script>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tipoGeneral = document.getElementById('tipo_general');
            const tipo = document.getElementById('tipo');

            const carrocerias = {
                automovil: ['Sedán', 'Hatchback', 'Coupé', 'SUV', 'Convertible'],
                camion: ['Autobus', 'Microbus', 'Caja seca', 'Plataforma', 'Volteo', 'Refrigerado', 'Tracto'],
                camioneta: ['Pick-up', 'Panel', 'Vagoneta', 'Furgoneta'],
                motocicleta: ['Trabajo', 'Cruisier', 'Doble Propósito', 'Scooter', 'Enduro', 'Naked', 'Pista'],
                bicicleta: ['Montaña', 'Ruta', 'BMX'],
                remolque: ['Plataforma', 'Caja cerrada', 'Cama baja', 'Refrigerado'],
                semoviente: ['Caballo', 'Burro', 'Vaca', 'Otro animal de tiro']
            };

            tipoGeneral.addEventListener('change', function () {
                const seleccion = this.value;
                tipo.innerHTML = '<option value="">-- Seleccione --</option>';

                if (carrocerias[seleccion]) {
                    carrocerias[seleccion].forEach(function (opcion) {
                        const opt = document.createElement('option');
                        opt.value = opcion;
                        opt.textContent = opcion;
                        tipo.appendChild(opt);
                    });
                }
            });

            const oldTipoGeneral = "{{ old('tipo_general') }}";
            const oldTipo = "{{ old('tipo') }}";
            if (oldTipoGeneral) {
                tipoGeneral.value = oldTipoGeneral;
                tipoGeneral.dispatchEvent(new Event('change'));
                tipo.value = oldTipo;
            }
        });
    </script>

@stop
