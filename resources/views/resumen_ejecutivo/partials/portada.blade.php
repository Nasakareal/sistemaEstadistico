<div class="cover-slide">

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
            AGENDA DE<br>PROXIMIDAD SOCIAL
        </h1>

        <div class="cover-slide__divider"></div>

        <h2 class="cover-slide__date">
            {{ \Carbon\Carbon::parse($fecha)->translatedFormat('d \d\e F \d\e Y') }}
        </h2>

        <p class="cover-slide__subtitle">
            Corte de las 20:00 hrs.
        </p>
    </div>

</div>
