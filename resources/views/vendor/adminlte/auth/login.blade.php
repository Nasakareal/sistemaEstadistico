@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('adminlte_css_pre')
    <link rel="icon" href="{{ asset('Favicons.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@stop

@php
    $login_url = View::getSection('login_url') ?? config('adminlte.login_url', 'login');
    $register_url = View::getSection('register_url') ?? config('adminlte.register_url', 'register');
    $password_reset_url = View::getSection('password_reset_url') ?? config('adminlte.password_reset_url', 'password/reset');

    if (config('adminlte.use_route_url', false)) {
        $login_url = $login_url ? route($login_url) : '';
        $register_url = $register_url ? route($register_url) : '';
        $password_reset_url = $password_reset_url ? route($password_reset_url) : '';
    } else {
        $login_url = $login_url ? url($login_url) : '';
        $register_url = $register_url ? url($register_url) : '';
        $password_reset_url = $password_reset_url ? url($password_reset_url) : '';
    }
@endphp

@section('adminlte_css')
<style>
    :root{
        --bg0:#070B14;
        --bg1:#0B1224;
        --text:#EAF0FF;
        --muted: rgba(234,240,255,.75);
        --stroke: rgba(255,255,255,.14);
        --card: rgba(255,255,255,.08);
        --card2: rgba(255,255,255,.06);
        --brand:#2DA8FF;
        --brand2:#7C5CFF;
        --shadow: 0 18px 55px rgba(0,0,0,.45);
    }

    /* Fondo general (auth) */
    body.login-page{
        font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif !important;
        color: var(--text) !important;
        background:
            radial-gradient(1200px 900px at 20% 10%, rgba(45,168,255,.25), transparent 60%),
            radial-gradient(1000px 800px at 80% 20%, rgba(124,92,255,.22), transparent 55%),
            radial-gradient(900px 650px at 60% 85%, rgba(25,211,140,.12), transparent 60%),
            linear-gradient(180deg, var(--bg0), var(--bg1) 60%, #050813) !important;
        min-height: 100vh;
    }

    /* Contenedor principal */
    .login-box{
        width: min(420px, calc(100% - 44px));
    }

    /* Card glass */
    .login-box .card{
        background: linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.05)) !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        border-radius: 22px !important;
        box-shadow: var(--shadow) !important;
        overflow: hidden;
    }

    /* Header */
    .login-logo, .login-box-msg{
        color: rgba(234,240,255,.92) !important;
    }

    .login-box .card-header,
    .login-box .card-header .login-box-msg{
        background: rgba(0,0,0,.14) !important;
        border-bottom: 1px solid rgba(255,255,255,.10) !important;
    }

    /* Texto del header que ponemos abajo */
    .sv-kicker{
        display:flex;
        align-items:center;
        gap: 10px;
        justify-content: center;
        margin: 6px 0 10px;
        color: rgba(234,240,255,.88);
        font-weight: 800;
        font-size: 12.5px;
        letter-spacing: .35px;
    }
    .sv-dot{
        width: 8px; height: 8px; border-radius: 999px;
        background: #19D38C;
        box-shadow: 0 0 0 5px rgba(25,211,140,.14);
    }

    /* Body */
    .login-box .card-body{
        background: transparent !important;
        padding: 18px 18px 16px !important;
    }

    /* Inputs premium */
    .login-box .form-control{
        background: rgba(0,0,0,.18) !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        color: rgba(234,240,255,.92) !important;
        border-radius: 14px !important;
        height: 44px;
        padding-left: 14px;
        transition: .18s ease;
    }
    .login-box .form-control::placeholder{
        color: rgba(234,240,255,.55) !important;
    }
    .login-box .form-control:focus{
        box-shadow: none !important;
        border-color: rgba(45,168,255,.45) !important;
        background: rgba(0,0,0,.22) !important;
    }

    /* Iconos a la derecha */
    .login-box .input-group-text{
        background: rgba(0,0,0,.18) !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        border-left: none !important;
        color: rgba(234,240,255,.78) !important;
        border-radius: 0 14px 14px 0 !important;
        width: 44px;
        justify-content: center;
    }
    .login-box .input-group .form-control{
        border-right: none !important;
        border-radius: 14px 0 0 14px !important;
    }

    /* iCheck */
    .icheck-primary label{
        color: rgba(234,240,255,.78) !important;
        font-weight: 600;
        font-size: 13px;
    }

    /* Botón principal */
    .login-box .btn-primary{
        border: 1px solid rgba(45,168,255,.35) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22)) !important;
        border-radius: 14px !important;
        font-weight: 800 !important;
        height: 44px;
        box-shadow: 0 18px 55px rgba(0,0,0,.30);
        transition: .18s ease;
    }
    .login-box .btn-primary:hover{
        transform: translateY(-1px);
        border-color: rgba(45,168,255,.55) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30)) !important;
    }

    /* Links */
    .login-box a{
        color: rgba(234,240,255,.78) !important;
        font-weight: 700;
    }
    .login-box a:hover{
        color: rgba(234,240,255,.92) !important;
        text-decoration: none;
    }

    /* Separadores y footer */
    .login-box .card-footer{
        background: transparent !important;
        border-top: 1px solid rgba(255,255,255,.10) !important;
        padding: 14px 18px 18px !important;
    }

    /* Mensajes de error */
    .invalid-feedback{
        color: #FFCC66 !important;
        font-weight: 700;
    }
</style>
@stop


@section('auth_body')
    <form action="{{ $login_url }}" method="POST">
        @csrf

        {{-- Email --}}
        <div class="input-group mb-3">
            <input
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                placeholder="{{ __('adminlte::adminlte.email') }}"
                required
                autofocus>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        {{-- Password --}}
        <div class="input-group mb-3">
            <input
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="{{ __('adminlte::adminlte.password') }}"
                required>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <div class="row" style="align-items:center;">
            <div class="col-7">
                <div class="icheck-primary" title="{{ __('adminlte::adminlte.remember_me_hint') }}">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">
                        {{ __('adminlte::adminlte.remember_me') }}
                    </label>
                </div>
            </div>

            <div class="col-5">
                <button
                    type="submit"
                    class="btn btn-primary btn-block">
                    <span class="fas fa-sign-in-alt"></span>
                    {{ __('adminlte::adminlte.sign_in') }}
                </button>
            </div>
        </div>
    </form>
@stop
