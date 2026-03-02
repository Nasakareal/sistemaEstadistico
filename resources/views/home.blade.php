@extends('adminlte::page')

@section('title', 'Sistema Estadistico')

@section('content')
    <div class="row">

        <div class="col-lg-12 mb-3">
            <div class="row">

                <div class="col-md-4">
                    <div class="sv-card">
                        <div class="sv-card__icon" style="background:rgba(45,168,255,.18);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Turno Activo</div>
                            <div class="sv-card__desc" style="font-size:18px;font-weight:900;">
                                {{ $turno_activo ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sv-card">
                        <div class="sv-card__icon" style="background:rgba(25,211,140,.18);">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">En Servicio</div>
                            <div class="sv-card__desc" style="font-size:18px;font-weight:900;">
                                {{ $personal_en_servicio ?? 0 }}
                                <div style="font-size:12px;font-weight:800;opacity:.85;margin-top:4px;">
                                    OP: {{ $personal_operativos_en_servicio ?? 0 }} &nbsp; | &nbsp;
                                    ADM: {{ $personal_administrativos_en_servicio ?? 0 }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sv-card">
                        <div class="sv-card__icon" style="background:rgba(255,193,7,.18);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="sv-card__body">
                            <div class="sv-card__title">Total Activos</div>
                            <div class="sv-card__desc" style="font-size:18px;font-weight:900;">
                                {{ $total_activos ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-lg-12">
            <div class="sv-feed">
                <div class="sv-feed__title">Feed</div>

                <div id="svFeedList">
                    @foreach(($feed_items ?? []) as $item)
                        <div class="sv-post" data-type="{{ $item['type'] }}" data-id="{{ $item['id'] }}">
                            <div class="sv-post__head">
                                <div class="sv-post__who">
                                    <div class="sv-post__avatar">{{ mb_substr($item['user_name'], 0, 1) }}</div>
                                    <div class="sv-post__meta">
                                        <div class="sv-post__name">{{ $item['user_name'] }}</div>
                                        <div class="sv-post__time">{{ $item['created_at'] }}</div>
                                    </div>
                                </div>
                                <div class="sv-post__badge sv-post__badge--{{ strtolower($item['type']) }}">{{ $item['type'] }}</div>
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
                    @endforeach
                </div>

                <div id="svFeedLoading" class="sv-feed__loading" style="display:none;">Cargando...</div>
                <div id="svFeedEnd" class="sv-feed__end" style="display:none;">No hay más elementos.</div>
                <div id="svFeedSentinel" style="height:1px;"></div>
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

    .sv-hero{
        margin: 10px 0 12px;
        border-radius: 26px;
        border: 1px solid rgba(255,255,255,.12);
        background:
            radial-gradient(700px 280px at 20% 30%, rgba(45,168,255,.20), transparent 60%),
            radial-gradient(700px 280px at 80% 30%, rgba(124,92,255,.18), transparent 60%),
            linear-gradient(180deg, rgba(255,255,255,.10), rgba(255,255,255,.04));
        box-shadow: var(--sv-shadow);
        overflow: hidden;
    }
    .sv-hero__inner{ padding: 18px 18px 16px; text-align: center; }
    .sv-hero__badge{
        display:inline-flex; align-items:center; gap:10px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(0,0,0,.18);
        border: 1px solid rgba(255,255,255,.10);
        color: rgba(234,240,255,.85);
        font-weight: 800;
        font-size: 12px;
        letter-spacing: .35px;
    }
    .sv-dot{
        width: 8px; height: 8px; border-radius: 999px;
        background: #19D38C;
        box-shadow: 0 0 0 5px rgba(25,211,140,.14);
        display:inline-block;
    }
    .sv-hero__title{
        margin-top: 10px;
        font-weight: 950;
        letter-spacing: -.6px;
        font-size: clamp(22px, 2.3vw, 30px);
        color: var(--sv-text);
    }
    .sv-hero__subtitle{
        margin-top: 6px;
        font-weight: 650;
        font-size: 13px;
        color: var(--sv-muted);
    }

    .sv-card{
        display:flex;
        gap: 14px;
        padding: 14px;
        margin-bottom: 16px;
        border-radius: var(--sv-radius);
        border: 1px solid var(--sv-stroke);
        background: linear-gradient(180deg, var(--sv-card), var(--sv-card2));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        transition: .18s ease;
        min-height: 108px;
    }
    .sv-card:hover{
        transform: translateY(-2px);
        border-color: rgba(45,168,255,.28);
        box-shadow: 0 18px 55px rgba(0,0,0,.30);
    }

    .sv-card__icon{
        width: 52px; height: 52px;
        border-radius: 18px;
        display:grid; place-items:center;
        border: 1px solid rgba(255,255,255,.14);
        box-shadow: 0 12px 25px rgba(0,0,0,.22);
        flex: 0 0 auto;
    }
    .sv-card__icon i{
        font-size: 20px;
        color: rgba(255,255,255,.95);
    }

    .sv-card__body{ flex: 1; min-width: 0; }
    .sv-card__title{
        font-weight: 900;
        font-size: 14px;
        color: var(--sv-text);
        line-height: 1.15;
    }
    .sv-card__desc{
        margin-top: 6px;
        font-weight: 650;
        font-size: 12.5px;
        color: var(--sv-muted);
    }

    .sv-btn{
        margin-top: 10px;
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

    .sv-btn--ghost{
        background: rgba(0,0,0,.18) !important;
        border: 1px solid rgba(255,255,255,.12) !important;
        color: rgba(234,240,255,.88) !important;
    }
    .sv-btn--ghost:hover{
        background: rgba(0,0,0,.22) !important;
        border-color: rgba(255,255,255,.16) !important;
        transform: none;
    }

    .sv-card--disabled{
        opacity: .78;
    }

    .sv-feed{
        max-width: 760px;
        margin: 0 auto 16px auto;
        border-radius: 22px;
        border: 1px solid rgba(255,255,255,.12);
        background: linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.04));
        box-shadow: 0 10px 35px rgba(0,0,0,.22);
        padding: 14px;
    }
    .sv-feed__title{
        font-weight: 950;
        color: var(--sv-text);
        letter-spacing: -.3px;
        margin: 2px 2px 12px;
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
        width: 36px; height: 36px;
        border-radius: 999px;
        display:grid; place-items:center;
        font-weight: 950;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(45,168,255,.18);
        color: rgba(234,240,255,.95);
        flex: 0 0 auto;
    }
    .sv-post__meta{ min-width:0; }
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
    .sv-post__badge--hecho{ background: rgba(25,211,140,.14); border-color: rgba(25,211,140,.22); }
    .sv-post__badge--actividad{ background: rgba(255,193,7,.14); border-color: rgba(255,193,7,.22); }

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

        return `
            <div class="sv-post" data-type="${esc(item.type)}" data-id="${esc(item.id)}">
                <div class="sv-post__head">
                    <div class="sv-post__who">
                        <div class="sv-post__avatar">${esc(avatar)}</div>
                        <div class="sv-post__meta">
                            <div class="sv-post__name">${esc(item.user_name)}</div>
                            <div class="sv-post__time">${esc(item.created_at)}</div>
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
