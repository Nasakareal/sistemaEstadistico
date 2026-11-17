<div class="card card-outline card-secondary mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center">
            <a href="{{ route('archivo_siniestros.index') }}" class="btn btn-sm {{ request()->routeIs('archivo_siniestros.index', 'archivo_siniestros.buscar', 'archivo_siniestros.libros.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1">
                <i class="fa-solid fa-book"></i> Libros
            </a>
            <a href="{{ route('archivo_siniestros.mapa') }}" class="btn btn-sm {{ request()->routeIs('archivo_siniestros.mapa') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1">
                <i class="fa-solid fa-map-location-dot"></i> Mapa
            </a>
            @can('gestionar archivo siniestros')
                <a href="{{ route('archivo_siniestros.areas.index') }}" class="btn btn-sm {{ request()->routeIs('archivo_siniestros.areas.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1">
                    <i class="fa-solid fa-building"></i> Áreas
                </a>
                <a href="{{ route('archivo_siniestros.anaqueles.index') }}" class="btn btn-sm {{ request()->routeIs('archivo_siniestros.anaqueles.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1">
                    <i class="fa-solid fa-boxes-stacked"></i> Anaqueles
                </a>
                <a href="{{ route('archivo_siniestros.bloques.index') }}" class="btn btn-sm {{ request()->routeIs('archivo_siniestros.bloques.*') ? 'btn-primary' : 'btn-outline-primary' }} mr-2 mb-1">
                    <i class="fa-solid fa-table-cells"></i> Bloques
                </a>
            @endcan
        </div>
    </div>
</div>
