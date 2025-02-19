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
                                    <label for="marca">Marca</label>
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
                                           value="{{ old('modelo') }}" placeholder="Ingrese el modelo" required>
                                    @error('modelo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                             </div>

                             <!-- Tipo -->
                             <div class="col-md-3">
                                 <div class="form-group">
                                    <label for="tipo">Tipo</label>
                                    <input type="text" name="tipo" id="tipo" class="form-control @error('tipo') is-invalid @enderror" 
                                           value="{{ old('tipo') }}" placeholder="Ingrese el tipo de Carroceria" required>
                                    @error('tipo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                             </div>

                             <!-- Línea -->
                             <div class="col-md-3">
                                <div class="form-group">
                                    <label for="linea">Línea</label>
                                    <input type="text" name="linea" id="linea" class="form-control @error('linea') is-invalid @enderror" 
                                           value="{{ old('linea') }}" placeholder="Ingrese la línea" required>
                                    @error('linea')
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
                                    <label for="color">Color</label>
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
                                    <label for="placas">Placas</label>
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
                                           value="{{ old('estado_placas') }}" placeholder="Ingrese el estado de placas" required>
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
                                           value="{{ old('serie') }}" placeholder="Ingrese la serie" required>
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
                                    <label for="capacidad_personas">Capacidad</label>
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
                                    <label for="tipo_servicio">Tipo de Servicio</label>
                                    <input type="text" name="tipo_servicio" id="tipo_servicio" class="form-control @error('tipo_servicio') is-invalid @enderror" 
                                           value="{{ old('tipo_servicio') }}" placeholder="Ingrese el tipo de servicio" required>
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
                                           value="{{ old('tarjeta_circulacion_nombre') }}" placeholder="Ingrese el nombre de la tarjeta de circulación" required>
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
                                           value="{{ old('conductor_nombre') }}" placeholder="Ingrese el nombre del conductor" required>
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
                                           value="{{ old('domicilio') }}" placeholder="Ingrese el domicilio del conductor" required>
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
                                    <select name="sexo" id="sexo" class="form-control @error('sexo') is-invalid @enderror" required>
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
                                           value="{{ old('ocupacion') }}" placeholder="Ingrese la ocupación del conductor" required>
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
                                           value="{{ old('edad') }}" placeholder="Ingrese la edad del conductor" min="00" max="100" required>
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

                            <!-- Checkbox para licencia permanente -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="permanente">Licencia Permanente</label>
                                    <input type="checkbox" name="permanente" id="permanente" 
                                           class="@error('permanente') is-invalid @enderror" 
                                           {{ old('permanente') ? 'checked' : '' }}>
                                    @error('permanente')
                                        <span class="invalid-feedback" role="alert">
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
                                    <label for="partes_danadas">Partes Dañadas</label>
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
                                    <label for="monto_danos">Monto de los Daños</label>
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
                                    <label for="grua">Grúa</label>
                                    <input type="text" name="grua" id="grua"
                                           class="form-control @error('grua') is-invalid @enderror"
                                           value="{{ old('grua', 'N/A') }}" placeholder="Ingrese la grúa (opcional)">
                                    @error('grua')
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
                                    <input type="text" name="corralon" id="corralon"
                                           class="form-control @error('corralon') is-invalid @enderror"
                                           value="{{ old('corralon', 'N/A') }}" placeholder="Ingrese el corralón (opcional)">
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

                        <!-- Fotos -->
                        <div class="row">
                            <!-- Fotos -->
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
        .form-group label {
            font-weight: bold;
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
@stop
