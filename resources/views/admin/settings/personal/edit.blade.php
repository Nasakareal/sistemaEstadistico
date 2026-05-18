@extends('adminlte::page')

@section('title', 'Editar Personal')

@section('content_header')
    <h1>Editar Personal</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Modificar Datos del Elemento</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('personal.update', $personal->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="user_id">Usuario del sistema</label>
                                    <select name="user_id" id="user_id"
                                            class="form-control @error('user_id') is-invalid @enderror">
                                        <option value="">Sin usuario</option>

                                        @if (isset($usuarioActual) && $usuarioActual)
                                            <option value="{{ $usuarioActual->id }}" selected>
                                                {{ $usuarioActual->name }}{{ $usuarioActual->email ? ' — ' . $usuarioActual->email : '' }}
                                            </option>
                                        @endif

                                        @foreach ($usuariosDisponibles as $u)
                                            @if(!isset($usuarioActual) || !$usuarioActual || (int)$u->id !== (int)$usuarioActual->id)
                                                <option value="{{ $u->id }}" {{ old('user_id', $personal->user_id) == $u->id ? 'selected' : '' }}>
                                                    {{ $u->name }}{{ $u->email ? ' — ' . $u->email : '' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="numero_empleado">Número de empleado</label>
                                    <input type="text" name="numero_empleado" id="numero_empleado"
                                           class="form-control @error('numero_empleado') is-invalid @enderror"
                                           value="{{ old('numero_empleado', $personal->numero_empleado) }}">
                                    @error('numero_empleado')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="foto">Foto</label>
                                    <input type="file" name="foto" id="foto"
                                           class="form-control-file @error('foto') is-invalid @enderror"
                                           accept="image/jpeg,image/png,image/webp">
                                    @error('foto')
                                        <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @if($personal->foto)
                                        <small class="form-text text-muted">
                                            Foto actual: {{ basename($personal->foto) }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre">Nombre(s)</label>
                                    <input type="text" name="nombre" id="nombre"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           value="{{ old('nombre', $personal->nombre) }}" required>
                                    @error('nombre')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ap_paterno">Apellido Paterno</label>
                                    <input type="text" name="ap_paterno" id="ap_paterno"
                                           class="form-control @error('ap_paterno') is-invalid @enderror"
                                           value="{{ old('ap_paterno', $personal->ap_paterno) }}">
                                    @error('ap_paterno')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ap_materno">Apellido Materno</label>
                                    <input type="text" name="ap_materno" id="ap_materno"
                                           class="form-control @error('ap_materno') is-invalid @enderror"
                                           value="{{ old('ap_materno', $personal->ap_materno) }}">
                                    @error('ap_materno')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md">
                                <div class="form-group">
                                    <label for="curp">CURP</label>
                                    <input type="text" name="curp" id="curp"
                                           class="form-control @error('curp') is-invalid @enderror"
                                           value="{{ old('curp', $personal->curp) }}">
                                    @error('curp')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="form-group">
                                    <label for="rfc">RFC</label>
                                    <input type="text" name="rfc" id="rfc"
                                           class="form-control @error('rfc') is-invalid @enderror"
                                           value="{{ old('rfc', $personal->rfc) }}">
                                    @error('rfc')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="form-group">
                                    <label for="cuip">CUIP</label>
                                    <input type="text" name="cuip" id="cuip"
                                           class="form-control @error('cuip') is-invalid @enderror"
                                           value="{{ old('cuip', $personal->cuip) }}">
                                    @error('cuip')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="form-group">
                                    <label for="cup">CUP</label>
                                    <input type="text" name="cup" id="cup"
                                           class="form-control @error('cup') is-invalid @enderror"
                                           value="{{ old('cup', $personal->cup) }}">
                                    @error('cup')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md">
                                <div class="form-group">
                                    <label for="grado">Grado</label>
                                    <input type="text" name="grado" id="grado"
                                           class="form-control @error('grado') is-invalid @enderror"
                                           value="{{ old('grado', $personal->grado) }}">
                                    @error('grado')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="puesto">Puesto</label>
                                    <input type="text" name="puesto" id="puesto"
                                           class="form-control @error('puesto') is-invalid @enderror"
                                           value="{{ old('puesto', $personal->puesto) }}">
                                    @error('puesto')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="adscripcion">Adscripción</label>
                                    <input type="text" name="adscripcion" id="adscripcion"
                                           class="form-control @error('adscripcion') is-invalid @enderror"
                                           value="{{ old('adscripcion', $personal->adscripcion) }}">
                                    @error('adscripcion')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">Área</label>
                                    <input type="text" name="area" id="area"
                                           class="form-control @error('area') is-invalid @enderror"
                                           value="{{ old('area', $personal->area) }}">
                                    @error('area')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad</label>
                                    <select name="unidad_id" id="unidad_id"
                                            class="form-control @error('unidad_id') is-invalid @enderror" required>
                                        <option value="">Seleccione una unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}"
                                                {{ old('unidad_id', $personal->unidad_id) == $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="turno_id">Turno</label>
                                    <select name="turno_id" id="turno_id"
                                            class="form-control @error('turno_id') is-invalid @enderror">
                                        <option value="">Sin turno</option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}"
                                                {{ old('turno_id', $personal->turno_id) == $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="patrulla_id">Patrulla</label>
                                    <select name="patrulla_id" id="patrulla_id"
                                            class="form-control @error('patrulla_id') is-invalid @enderror">
                                        <option value="">Sin patrulla</option>
                                        @foreach ($patrullas as $p)
                                            <option value="{{ $p->id }}"
                                                data-unidad="{{ (int) $p->unidad_id }}"
                                                {{ old('patrulla_id', $personal->patrulla_id) == $p->id ? 'selected' : '' }}>
                                                {{ $p->numero_economico }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patrulla_id')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="categoria">Categoría</label>
                                    <select name="categoria" id="categoria"
                                            class="form-control @error('categoria') is-invalid @enderror" required>
                                        <option value="">Seleccione</option>
                                        @foreach (($categoriasPersonal ?? ['OPERATIVO','ADMINISTRATIVO']) as $cat)
                                            <option value="{{ $cat }}" {{ old('categoria', $personal->categoria ?? '') == $cat ? 'selected' : '' }}>
                                                {{ $cat }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoria')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estatus">Estatus</label>
                                    <select name="estatus" id="estatus"
                                            class="form-control @error('estatus') is-invalid @enderror" required>
                                        <option value="ACTIVO" {{ old('estatus', $personal->estatus) == 'ACTIVO' ? 'selected' : '' }}>ACTIVO</option>
                                        <option value="INACTIVO" {{ old('estatus', $personal->estatus) == 'INACTIVO' ? 'selected' : '' }}>INACTIVO</option>
                                        <option value="SUSPENDIDO" {{ old('estatus', $personal->estatus) == 'SUSPENDIDO' ? 'selected' : '' }}>SUSPENDIDO</option>
                                        <option value="BAJA" {{ old('estatus', $personal->estatus) == 'BAJA' ? 'selected' : '' }}>BAJA</option>
                                    </select>
                                    @error('estatus')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_ingreso">Fecha de Ingreso</label>
                                    <input type="date" name="fecha_ingreso" id="fecha_ingreso"
                                           class="form-control @error('fecha_ingreso') is-invalid @enderror"
                                           value="{{ old('fecha_ingreso', optional($personal->fecha_ingreso)->format('Y-m-d')) }}">
                                    @error('fecha_ingreso')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_baja">Fecha de Baja</label>
                                    <input type="date" name="fecha_baja" id="fecha_baja"
                                           class="form-control @error('fecha_baja') is-invalid @enderror"
                                           value="{{ old('fecha_baja', optional($personal->fecha_baja)->format('Y-m-d')) }}">
                                    @error('fecha_baja')
                                        <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Actualizar
                                </button>
                                <a href="{{ route('personal.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-ban"></i> Cancelar
                                </a>
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
    .form-group label { font-weight: bold; }

    #user_id.form-control,
    select.form-control {
        background-color: #111827 !important;
        color: #e5e7eb !important;
        border: 1px solid rgba(255,255,255,.18) !important;
    }

    #user_id.form-control:focus,
    select.form-control:focus {
        background-color: #0b1220 !important;
        color: #e5e7eb !important;
        border-color: rgba(59,130,246,.65) !important;
        box-shadow: 0 0 0 .2rem rgba(59,130,246,.25) !important;
    }

    #user_id option,
    select.form-control option {
        background-color: #111827 !important;
        color: #e5e7eb !important;
    }

    #user_id option:checked,
    select.form-control option:checked {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    #user_id option:disabled,
    select.form-control option:disabled {
        color: rgba(229,231,235,.55) !important;
    }
</style>
@stop

@section('js')
<script>
(function () {

    function filterPatrullasByUnidad() {
        const unidadSel = document.getElementById('unidad_id');
        const patrullaSel = document.getElementById('patrulla_id');
        if (!unidadSel || !patrullaSel) return;

        const unidadId = unidadSel.value;

        for (const opt of patrullaSel.options) {
            if (!opt.value) continue;
            opt.hidden = opt.dataset.unidad !== unidadId;
        }

        if (patrullaSel.selectedOptions.length &&
            patrullaSel.selectedOptions[0].hidden) {
            patrullaSel.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const unidadSel = document.getElementById('unidad_id');
        if (unidadSel) unidadSel.addEventListener('change', filterPatrullasByUnidad);
        filterPatrullasByUnidad();
    });

})();

@if ($errors->any())
Swal.fire({
    icon: 'error',
    title: 'Errores en el formulario',
    html: `
        <ul style="text-align:left;">
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
