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
                                    <label for="apellido_paterno">Apellido paterno</label>
                                    <input type="text" name="apellido_paterno" id="apellido_paterno"
                                           class="form-control @error('apellido_paterno') is-invalid @enderror"
                                           value="{{ old('apellido_paterno', $user->apellido_paterno) }}" placeholder="Ingrese el apellido paterno">
                                    @error('apellido_paterno')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="apellido_materno">Apellido materno</label>
                                    <input type="text" name="apellido_materno" id="apellido_materno"
                                           class="form-control @error('apellido_materno') is-invalid @enderror"
                                           value="{{ old('apellido_materno', $user->apellido_materno) }}" placeholder="Ingrese el apellido materno">
                                    @error('apellido_materno')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombres">Nombre(s)</label>
                                    <input type="text" name="nombres" id="nombres"
                                           class="form-control @error('nombres') is-invalid @enderror"
                                           value="{{ old('nombres', $user->nombres ?: $user->name) }}" placeholder="Ingrese el/los nombre(s)" required>
                                    @error('nombres')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
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

                            @role('Superadmin')
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="telefono">WhatsApp autorizado para respuestas de la API</label>
                                    <input type="text" name="telefono" id="telefono"
                                           class="form-control @error('telefono') is-invalid @enderror"
                                           value="{{ old('telefono', $user->telefono) }}" placeholder="Ejemplo: 4434765057">
                                    @error('telefono')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Si queda vacío, el bot oficial ignorará sus mensajes y no le responderá.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="telefono_whatsapp_operativo">WhatsApp operativo para tiempo de reacción</label>
                                    <input type="text" name="telefono_whatsapp_operativo" id="telefono_whatsapp_operativo"
                                           class="form-control @error('telefono_whatsapp_operativo') is-invalid @enderror"
                                           value="{{ old('telefono_whatsapp_operativo', $user->telefono_whatsapp_operativo) }}" placeholder="Ejemplo: 4434765057">
                                    @error('telefono_whatsapp_operativo')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Identifica al usuario y su patrulla cuando reporta asignación o arribo en el grupo.</small>
                                </div>
                            </div>
                            @endrole
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="role_id">Rol</label>
                                    <select name="role_id" id="role_id"
                                            class="form-control @error('role_id') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('role_id', optional($user->roles->first())->id) ? '' : 'selected' }}>Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            @if(!(auth()->user()->hasRole('Administrador') && !auth()->user()->hasRole('Superadmin') && $role->name === 'Administrador'))
                                                @php
                                                    $unidadRolId = $role->unidadIdEfectiva();
                                                    $unidadRolNombre = $role->unidadEfectivaNombre();
                                                @endphp
                                                <option value="{{ $role->id }}"
                                                        data-unidad-id="{{ $unidadRolId }}"
                                                        {{ (string) old('role_id', optional($user->roles->first())->id) === (string) $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}{{ !is_null($unidadRolId) ? ' - ' . ($unidadRolNombre ?? 'SIN UNIDAD') : ' - GLOBAL' }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="text-muted">Solo aparecen los roles que puedes asignar.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group password-group">
                                    <label for="password">Contraseña</label>
                                    <div class="input-group password-wrapper">
                                        <input type="password" name="password" id="password"
                                               class="form-control @error('password') is-invalid @enderror"
                                               placeholder="Ingrese una nueva contraseña (opcional)">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-password-toggle" data-target="password">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        @error('password')
                                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group password-group">
                                    <label for="password_confirmation">Repetir Contraseña</label>
                                    <div class="input-group password-wrapper">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                               class="form-control"
                                               placeholder="Confirme la nueva contraseña (opcional)">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-password-toggle" data-target="password_confirmation">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
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
                                    <small class="text-muted">La unidad principal debe coincidir con el rol si el rol pertenece a una unidad específica.</small>
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
                                    <small class="text-muted">Para Encargados o jefes de turno esto es importante.</small>
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
                                    <small class="text-muted">Esto es del usuario, no del hecho.</small>
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
                                    <small class="text-muted">Solo aplica si la unidad principal es DELEGACIONES.</small>
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
                                    <small class="text-muted">Solo aplica si la unidad principal es CARRETERAS.</small>
                                </div>
                            </div>
                        </div>

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
    .form-group label {
        font-weight: bold;
        color: #e5e7eb;
    }

    .form-control,
    .custom-select,
    select.form-control {
        background: rgba(15, 23, 42, 0.75) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        border-radius: 18px !important;
        box-shadow: none !important;
    }

    .form-control:focus,
    .custom-select:focus,
    select.form-control:focus {
        background: rgba(15, 23, 42, 0.92) !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
    }

    select.form-control option,
    .custom-select option,
    select option {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.65) !important;
    }

    .text-muted,
    small.text-muted {
        color: rgba(255, 255, 255, 0.72) !important;
    }

    .card {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(49, 46, 129, 0.92)) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 24px !important;
        overflow: hidden;
    }

    .card-header {
        background: rgba(255, 255, 255, 0.04) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    }

    .card-title,
    .content-header h1 {
        color: #ffffff !important;
        font-weight: 700;
    }

    .btn-primary,
    .btn-secondary {
        border-radius: 16px !important;
    }

    .password-wrapper .form-control {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .btn-password-toggle {
        height: 100%;
        min-width: 48px;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        border-top-right-radius: 18px !important;
        border-bottom-right-radius: 18px !important;
        border: 1px solid rgba(255, 255, 255, 0.14) !important;
        border-left: 0 !important;
        background: rgba(15, 23, 42, 0.95) !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }

    .btn-password-toggle:hover,
    .btn-password-toggle:focus {
        background: rgba(30, 41, 59, 1) !important;
        color: #ffffff !important;
        outline: none !important;
        box-shadow: none !important;
    }

    .password-wrapper .invalid-feedback {
        width: 100%;
    }
</style>
@stop

@section('js')
    <script>
        (function () {
            const UNIDAD_DELEGACIONES_ID = @json($unidadDelegacionesId);
            const UNIDAD_CARRETERAS_ID = @json($unidadCarreterasId);

            function getSelectedRoleOption() {
                const roleSelect = document.getElementById('role_id');
                if (!roleSelect) return null;
                return roleSelect.options[roleSelect.selectedIndex] || null;
            }

            function limpiarTelefonoInput(event) {
                const telefonoInput = event.currentTarget;
                telefonoInput.value = telefonoInput.value.replace(/\D+/g, '');
            }

            function togglePassword(button) {
                const targetId = button.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = button.querySelector('i');

                if (!input || !icon) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }

            function syncUnidadConRol() {
                const roleOption = getSelectedRoleOption();
                const unidadSelect = document.getElementById('unidad_id');

                if (!roleOption || !unidadSelect) return;

                const unidadRol = roleOption.getAttribute('data-unidad-id');

                if (unidadRol && unidadRol !== 'null' && unidadRol !== '') {
                    unidadSelect.value = unidadRol;
                }
            }

            function toggleUbicacionEspecial() {
                const unidadSel = document.getElementById('unidad_id');
                const boxDelegacion = document.getElementById('box_delegacion');
                const boxDestacamento = document.getElementById('box_destacamento');
                const delegSel = document.getElementById('delegacion_id');
                const destacSel = document.getElementById('destacamento_id');

                if (!unidadSel || !boxDelegacion || !boxDestacamento || !delegSel || !destacSel) return;

                const unidadId = unidadSel.value ? parseInt(unidadSel.value, 10) : null;
                const showDelegacion = UNIDAD_DELEGACIONES_ID !== null && unidadId === parseInt(UNIDAD_DELEGACIONES_ID, 10);
                const showDestacamento = UNIDAD_CARRETERAS_ID !== null && unidadId === parseInt(UNIDAD_CARRETERAS_ID, 10);

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
                const roleSel = document.getElementById('role_id');
                const unidadSel = document.getElementById('unidad_id');
                const telefonoInputs = document.querySelectorAll('#telefono, #telefono_whatsapp_operativo');
                const form = document.querySelector('form');
                const passwordButtons = document.querySelectorAll('.btn-password-toggle');

                if (roleSel) {
                    roleSel.addEventListener('change', function () {
                        syncUnidadConRol();
                        toggleUbicacionEspecial();
                    });
                }

                if (unidadSel) {
                    unidadSel.addEventListener('change', toggleUbicacionEspecial);
                }

                telefonoInputs.forEach(function (telefonoInput) {
                    telefonoInput.addEventListener('input', limpiarTelefonoInput);
                    telefonoInput.addEventListener('blur', limpiarTelefonoInput);
                });

                passwordButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        togglePassword(button);
                    });
                });

                if (form) {
                    form.addEventListener('submit', function () {
                        limpiarTelefonoInput();
                    });
                }

                syncUnidadConRol();
                toggleUbicacionEspecial();
                limpiarTelefonoInput();
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
