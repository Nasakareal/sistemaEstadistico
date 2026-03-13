@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <h1>Editar Usuario</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Actualizar Datos del Usuario</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="name">Nombre del Usuario</label>
                                    <input type="text" name="name" id="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $user->name) }}" placeholder="Ingrese el nombre" required>
                                    @error('name')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $user->email) }}" placeholder="Ingrese el correo electrónico" required>
                                    @error('email')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">Área</label>
                                    <input type="text" name="area" id="area"
                                           class="form-control @error('area') is-invalid @enderror"
                                           value="{{ old('area', $user->area ?? '') }}" placeholder="Ingrese el área">
                                    @error('area')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="role">Rol</label>
                                    <select name="role" id="role"
                                            class="form-control @error('role') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}"
                                                {{ old('role', optional($user->roles->first())->name) == $role->name ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Si el rol es Coordinador, puedes asignarle varias unidades abajo.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password">Contraseña</label>
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="Ingrese una nueva contraseña (opcional)">
                                    @error('password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password_confirmation">Repetir Contraseña</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="form-control"
                                           placeholder="Confirme la nueva contraseña (opcional)">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_id">Unidad (principal)</label>
                                    <select name="unidad_id" id="unidad_id"
                                            class="form-control @error('unidad_id') is-invalid @enderror">
                                        <option value="">Sin unidad</option>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}"
                                                {{ (string) old('unidad_id', $user->unidad_id) === (string) $u->id ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Para Subdirector/Encargado normalmente va aquí.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="turno_id">Turno</label>
                                    <select name="turno_id" id="turno_id"
                                            class="form-control @error('turno_id') is-invalid @enderror">
                                        <option value="">Sin turno</option>
                                        @foreach ($turnos as $t)
                                            <option value="{{ $t->id }}"
                                                {{ (string) old('turno_id', $user->turno_id) === (string) $t->id ? 'selected' : '' }}>
                                                {{ $t->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('turno_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Para Encargados (jefes de turno) esto es clave.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="patrulla_id">Patrulla (número económico)</label>
                                    <select name="patrulla_id" id="patrulla_id"
                                            class="form-control @error('patrulla_id') is-invalid @enderror">
                                        <option value="">Sin patrulla</option>
                                        @foreach ($patrullas as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string) old('patrulla_id', $user->patrulla_id) === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->numero_economico }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('patrulla_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Esto es del usuario (su unidad móvil), no del hecho.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_delegacion" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="delegacion_id">Delegación</label>
                                    <select name="delegacion_id" id="delegacion_id"
                                            class="form-control @error('delegacion_id') is-invalid @enderror">
                                        <option value="">Seleccione una delegación</option>
                                        @foreach ($delegaciones as $d)
                                            <option value="{{ $d->id }}"
                                                {{ (string) old('delegacion_id', $user->delegacion_id) === (string) $d->id ? 'selected' : '' }}>
                                                {{ $d->nombre }}@if(!empty($d->clave)) ({{ $d->clave }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegacion_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">
                                        Solo aplica si la unidad principal es DELEGACIONES. Si cambias la unidad, se limpia automáticamente.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_destacamento" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destacamento_id">Destacamento</label>
                                    <select name="destacamento_id" id="destacamento_id"
                                            class="form-control @error('destacamento_id') is-invalid @enderror">
                                        <option value="">Seleccione un destacamento</option>
                                        @foreach ($destacamentos as $destacamento)
                                            <option value="{{ $destacamento->id }}"
                                                {{ (string) old('destacamento_id', $user->destacamento_id) === (string) $destacamento->id ? 'selected' : '' }}>
                                                {{ $destacamento->nombre }}@if(!empty($destacamento->clave)) ({{ $destacamento->clave }}) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('destacamento_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">
                                        Solo aplica si la unidad principal es CARRETERAS. Si cambias la unidad, se limpia automáticamente.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="box_unidades_extra" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="unidades_ids">Unidades adicionales (solo Coordinador)</label>
                                    <select name="unidades_ids[]" id="unidades_ids"
                                            class="form-control @error('unidades_ids') is-invalid @enderror" multiple>
                                        @foreach ($unidades as $u)
                                            <option value="{{ $u->id }}"
                                                {{ collect(old('unidades_ids', $unidadesExtraSeleccionadas ?? []))->contains($u->id) ? 'selected' : '' }}>
                                                {{ $u->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidades_ids')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @error('unidades_ids.*')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Si es Coordinador, aquí eliges las unidades que puede ver además de su unidad principal.</small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-check"></i> Guardar Cambios
                                </button>
                                <a href="{{ route('users.index') }}" class="btn btn-secondary">
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
    </style>
@stop

@section('js')
    <script>
        (function () {
            const UNIDAD_DELEGACIONES_ID = @json($unidadDelegacionesId);
            const UNIDAD_CARRETERAS_ID = @json($unidadCarreterasId);

            function toggleUnidadesExtra() {
                const role = (document.getElementById('role')?.value || '');
                const box = document.getElementById('box_unidades_extra');
                if (!box) return;
                box.style.display = (role === 'Coordinador') ? '' : 'none';
            }

            function toggleUbicacionEspecial() {
                const unidadSel = document.getElementById('unidad_id');
                const boxDelegacion = document.getElementById('box_delegacion');
                const boxDestacamento = document.getElementById('box_destacamento');
                const delegSel = document.getElementById('delegacion_id');
                const destacSel = document.getElementById('destacamento_id');

                if (!unidadSel || !boxDelegacion || !boxDestacamento || !delegSel || !destacSel) return;

                const unidadId = unidadSel.value ? parseInt(unidadSel.value, 10) : null;

                const showDelegacion = (UNIDAD_DELEGACIONES_ID !== null && unidadId === parseInt(UNIDAD_DELEGACIONES_ID, 10));
                const showDestacamento = (UNIDAD_CARRETERAS_ID !== null && unidadId === parseInt(UNIDAD_CARRETERAS_ID, 10));

                boxDelegacion.style.display = showDelegacion ? '' : 'none';
                boxDestacamento.style.display = showDestacamento ? '' : 'none';

                if (!showDelegacion) {
                    delegSel.value = '';
                }

                if (!showDestacamento) {
                    destacSel.value = '';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const roleSel = document.getElementById('role');
                if (roleSel) roleSel.addEventListener('change', toggleUnidadesExtra);

                const unidadSel = document.getElementById('unidad_id');
                if (unidadSel) unidadSel.addEventListener('change', toggleUbicacionEspecial);

                toggleUnidadesExtra();
                toggleUbicacionEspecial();
            });
        })();

        @if (session('success'))
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 1500
            });
        @endif

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Error en el formulario',
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
