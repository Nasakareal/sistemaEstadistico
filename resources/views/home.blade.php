@extends('adminlte::page')

@section('title', 'Sistema Estadistico')

@section('content')
    <div class="sv-home">
        @if(!empty($home_unidad_branding_asset))
            <div class="sv-home__watermark" aria-hidden="true">
                <img src="{{ asset($home_unidad_branding_asset) }}" alt="" role="presentation">
            </div>
        @endif

        <div class="sv-home__content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sv-feed">
                <div class="sv-feed__header">
                    <div>
                        <div class="sv-feed__title">Feed</div>
                        <div class="sv-feed__subtitle">Últimos registros de hechos y actividades</div>
                    </div>

                    @if(!empty($feed_puede_filtrar_unidades))
                        <form method="GET" action="{{ url('/home') }}" class="sv-filter">
                            <select name="unidad_id" class="form-control sv-filter__select" onchange="this.form.submit()">
                                <option value="TODAS" {{ ($feed_unidad_id ?? 'TODAS') === 'TODAS' ? 'selected' : '' }}>
                                    Todas las unidades
                                </option>

                                @foreach(($feed_unidades ?? []) as $unidad)
                                    <option value="{{ $unidad->id }}" {{ (string)($feed_unidad_id ?? '') === (string)$unidad->id ? 'selected' : '' }}>
                                        {{ $unidad->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>

                <div id="svFeedList">
                    @forelse(($feed_items ?? []) as $item)
                        <div class="sv-post" data-type="{{ $item['type'] }}" data-id="{{ $item['id'] }}">
                            <div class="sv-post__head">
                                <div class="sv-post__who">
                                    <div class="sv-post__avatar">{{ mb_substr($item['user_name'], 0, 1) }}</div>
                                    <div class="sv-post__meta">
                                        <div class="sv-post__name">{{ $item['user_name'] }}</div>
                                        <div class="sv-post__time">{{ $item['created_at'] }}</div>
                                        @if(!empty($item['delegacion_nombre']))
                                            <div class="sv-post__delegacion">
                                                <i class="fa-solid fa-location-dot"></i>
                                                <span>{{ $item['delegacion_nombre'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="sv-post__badge sv-post__badge--{{ strtolower($item['type']) }}">
                                    {{ $item['type'] }}
                                </div>
                            </div>

                            <div class="sv-post__body">
                                <div class="sv-post__text">{{ $item['resumen'] }}</div>

                                @if(!empty($item['foto_url']))
                                    <div class="sv-post__imgwrap">
                                        <img src="{{ $item['foto_url'] }}" class="sv-post__img" alt="foto">
                                    </div>
                                @endif

                                <div class="sv-post__actions">
                                    <a href="{{ $item['show_url'] }}" class="btn sv-btn sv-btn--mini">
                                        <i class="fas fa-arrow-right"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sv-empty">
                            <i class="fa-solid fa-rss"></i>
                            <div class="sv-empty__title">No hay elementos en el feed</div>
                            <div class="sv-empty__desc">Cuando existan registros aparecerán aquí.</div>
                        </div>
                    @endforelse
                </div>

                <div id="svFeedLoading" class="sv-feed__loading" style="display:none;">Cargando...</div>
                <div id="svFeedEnd" class="sv-feed__end" style="display:none;">No hay más elementos.</div>
                <div id="svFeedSentinel" style="height:1px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    :root{
        --sv-text: rgba(234,240,255,.92);
        --sv-muted: rgba(234,240,255,.65);
        --sv-stroke: rgba(255,255,255,.12);
        --sv-card: rgba(255,255,255,.08);
        --sv-card2: rgba(255,255,255,.05);
        --sv-shadow: 0 18px 55px rgba(0,0,0,.35);
        --sv-radius: 22px;
    }

    .sv-home{
        position: relative;
        isolation: isolate;
        min-height: calc(100vh - 130px);
    }

    .sv-home__watermark{
        position: sticky;
        top: 0;
        z-index: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        height: calc(100vh - 130px);
        margin-bottom: calc(-100vh + 130px);
        overflow: hidden;
        pointer-events: none;
        user-select: none;
    }

    .sv-home__watermark img{
        display: block;
        width: 68%;
        max-width: 900px;
        max-height: 92%;
        object-fit: contain;
        opacity: .032;
        transform: translateY(-2%);
    }

    .sv-home__content{
        position: relative;
        z-index: 1;
    }

    .sv-feed{
        max-width: 820px;
        margin: 0 auto 16px auto;
        border-radius: 22px;
        border: 1px solid rgba(255,255,255,.12);
        background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        padding: 14px;
    }

    .sv-feed__header{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 12px;
        margin: 2px 2px 14px;
        flex-wrap: wrap;
    }

    .sv-feed__title{
        font-weight: 950;
        color: var(--sv-text);
        letter-spacing: -.3px;
        font-size: 18px;
        line-height: 1.1;
    }

    .sv-feed__subtitle{
        margin-top: 4px;
        font-size: 12.5px;
        font-weight: 700;
        color: var(--sv-muted);
    }

    .sv-filter{
        margin: 0;
    }

    .sv-filter__select{
        min-width: 230px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(0,0,0,.22);
        color: rgba(234,240,255,.92);
        font-weight: 800;
        height: 42px;
    }

    .sv-filter__select option{
        color: #111;
    }

    .sv-post{
        border-radius: 18px;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(0,0,0,.16);
        padding: 12px;
        margin-bottom: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,.18);
    }

    .sv-post__head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 10px;
        margin-bottom: 8px;
    }

    .sv-post__who{
        display:flex;
        align-items:center;
        gap: 10px;
        min-width: 0;
    }

    .sv-post__avatar{
        width: 36px;
        height: 36px;
        border-radius: 999px;
        display:grid;
        place-items:center;
        font-weight: 950;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(45,168,255,.18);
        color: rgba(234,240,255,.95);
        flex: 0 0 auto;
    }

    .sv-post__meta{
        min-width:0;
    }

    .sv-post__name{
        font-weight: 900;
        color: var(--sv-text);
        line-height: 1.05;
    }

    .sv-post__time{
        font-size: 12px;
        color: var(--sv-muted);
        margin-top: 2px;
    }

    .sv-post__badge{
        font-size: 11px;
        font-weight: 950;
        letter-spacing: .35px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.14);
        color: rgba(234,240,255,.95);
        background: rgba(0,0,0,.18);
        flex: 0 0 auto;
    }

    .sv-post__badge--hecho{
        background: rgba(25,211,140,.14);
        border-color: rgba(25,211,140,.22);
    }

    .sv-post__badge--actividad{
        background: rgba(255,193,7,.14);
        border-color: rgba(255,193,7,.22);
    }

    .sv-post__text{
        font-weight: 700;
        color: rgba(234,240,255,.88);
        line-height: 1.25;
    }

    .sv-post__imgwrap{
        margin-top: 10px;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        background: rgba(0,0,0,.18);
        max-height: 320px;
    }

    .sv-post__img{
        display:block;
        width: 100%;
        height: 320px;
        object-fit: cover;
    }

    .sv-post__actions{
        display:flex;
        gap: 10px;
        margin-top: 10px;
    }

    .sv-btn{
        display:inline-flex;
        align-items:center;
        gap: 8px;
        border-radius: 14px;
        font-weight: 900;
        border: 1px solid rgba(45,168,255,.35) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.25), rgba(124,92,255,.22)) !important;
        color: rgba(234,240,255,.95) !important;
        padding: 8px 12px;
    }

    .sv-btn:hover{
        transform: translateY(-1px);
        border-color: rgba(45,168,255,.55) !important;
        background: linear-gradient(135deg, rgba(45,168,255,.34), rgba(124,92,255,.30)) !important;
        color: rgba(234,240,255,.98) !important;
    }

    .sv-btn--mini{
        padding: 7px 10px;
        border-radius: 12px;
        font-size: 12px;
        margin-top: 0;
    }

    .sv-feed__loading,
    .sv-feed__end{
        text-align:center;
        font-weight: 800;
        color: var(--sv-muted);
        padding: 10px 6px 4px;
    }

    .sv-empty{
        padding: 40px 20px;
        text-align: center;
        color: var(--sv-muted);
    }

    .sv-empty i{
        font-size: 34px;
        margin-bottom: 12px;
        color: rgba(255,255,255,.75);
    }

    .sv-empty__title{
        font-size: 16px;
        font-weight: 900;
        color: var(--sv-text);
    }

    .sv-empty__desc{
        margin-top: 6px;
        font-size: 13px;
        color: var(--sv-muted);
    }

    .sv-post__delegacion{
        margin-top: 4px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        line-height: 1.2;
        font-weight: 800;
        color: rgba(191,219,254,.95);
    }

    .sv-post__delegacion i{
        font-size: 11px;
    }

    @media (max-width: 767.98px){
        .sv-home{
            min-height: calc(100vh - 105px);
        }

        .sv-home__watermark img{
            width: 68%;
            max-height: 78vh;
        }

        .sv-home__watermark{
            height: calc(100vh - 105px);
            margin-bottom: calc(-100vh + 105px);
        }
    }
</style>
@stop

@section('js')
<script>
(function () {
    const list = document.getElementById('svFeedList');
    const loadingEl = document.getElementById('svFeedLoading');
    const endEl = document.getElementById('svFeedEnd');
    const sentinel = document.getElementById('svFeedSentinel');

    let loading = false;
    let ended = false;

    let cursorCreatedAt = @json($feed_next_cursor['cursor_created_at'] ?? null);
    let cursorId = @json($feed_next_cursor['cursor_id'] ?? null);
    const limit = @json($feed_limit ?? 12);
    const unidadId = @json($feed_unidad_id ?? null);

    function esc(s){
        return String(s ?? '')
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function postHtml(item){
        const badgeClass = String(item.type || '').toLowerCase();
        const avatar = (item.user_name || 'U').trim().substring(0,1);

        const img = item.foto_url
            ? `<div class="sv-post__imgwrap"><img src="${esc(item.foto_url)}" class="sv-post__img" alt="foto"></div>`
            : '';
        const delegacion = item.delegacion_nombre
            ? `<div class="sv-post__delegacion"><i class="fa-solid fa-location-dot"></i><span>${esc(item.delegacion_nombre)}</span></div>`
            : '';

        return `
            <div class="sv-post" data-type="${esc(item.type)}" data-id="${esc(item.id)}">
                <div class="sv-post__head">
                    <div class="sv-post__who">
                        <div class="sv-post__avatar">${esc(avatar)}</div>
                        <div class="sv-post__meta">
                            <div class="sv-post__name">${esc(item.user_name)}</div>
                            <div class="sv-post__time">${esc(item.created_at)}</div>
                            ${delegacion}
                        </div>
                    </div>
                    <div class="sv-post__badge sv-post__badge--${esc(badgeClass)}">${esc(item.type)}</div>
                </div>

                <div class="sv-post__body">
                    <div class="sv-post__text">${esc(item.resumen)}</div>
                    ${img}
                    <div class="sv-post__actions">
                        <a href="${esc(item.show_url)}" class="btn sv-btn sv-btn--mini">
                            <i class="fas fa-arrow-right"></i> Ver
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    async function fetchMore(){
        if (loading || ended) return;

        if (!cursorCreatedAt || !cursorId) {
            ended = true;
            endEl.style.display = 'block';
            return;
        }

        loading = true;
        loadingEl.style.display = 'block';

        try{
            const params = new URLSearchParams();
            params.set('limit', limit);
            params.set('cursor_created_at', cursorCreatedAt);
            params.set('cursor_id', cursorId);

            if (unidadId !== null && unidadId !== '') {
                params.set('unidad_id', unidadId);
            }

            const res = await fetch(`/home/feed?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const json = await res.json();

            if (Array.isArray(json.data) && json.data.length){
                list.insertAdjacentHTML('beforeend', json.data.map(postHtml).join(''));

                if (json.next_cursor){
                    cursorCreatedAt = json.next_cursor.cursor_created_at;
                    cursorId = json.next_cursor.cursor_id;
                } else {
                    ended = true;
                    endEl.style.display = 'block';
                }
            } else {
                ended = true;
                endEl.style.display = 'block';
            }
        }catch(e){
            console.error(e);
        }finally{
            loading = false;
            loadingEl.style.display = 'none';
        }
    }

    const io = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) fetchMore();
    }, { threshold: 0.1 });

    io.observe(sentinel);
})();
</script>
@stop
