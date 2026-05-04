@php
    $periodoTexto = $reporte['periodo']['texto']
        ?? \Carbon\Carbon::parse($fechaInicio ?? now()->startOfMonth())->translatedFormat('d \d\e F \d\e Y') . ' al ' . \Carbon\Carbon::parse($fechaFin ?? now())->translatedFormat('d \d\e F \d\e Y');
@endphp

<div class="cover-slide cover-slide--seguridad-vial">
    <div class="cover-slide__pleca">
        <img src="{{ asset('img/pleca.svg') }}" alt="Pleca institucional">
    </div>

    <div class="cover-slide__top">
        <div class="cover-slide__brand-left">
            <img src="{{ asset('img/michoacan_vertical.png') }}" alt="Gobierno de Michoacán">
        </div>

        <div class="cover-slide__brand-right">
            <img src="{{ asset('img/ssp.svg') }}" alt="Secretaría de Seguridad Pública">
        </div>
    </div>

    <div class="cover-slide__content">
        <div class="cover-slide__tag">Informe institucional</div>

        <h1 class="cover-slide__title">
            INFORME DE<br>SEGURIDAD VIAL
        </h1>

        <div class="cover-slide__divider"></div>

        <h2 class="cover-slide__date" id="svialCoverPeriod">
            {{ $periodoTexto }}
        </h2>

        <p class="cover-slide__subtitle">
            Consolidado general de todas las unidades
        </p>
    </div>
</div>
