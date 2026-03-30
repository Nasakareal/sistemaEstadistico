<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir Hecho</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
            overflow: hidden;
        }

        .head {
            background: #0f766e;
            color: #ffffff;
            padding: 18px 20px;
        }

        .head h1 {
            margin: 0;
            font-size: 20px;
        }

        .body {
            padding: 20px;
        }

        .texto {
            white-space: pre-wrap;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 18px;
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .media-grid img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #f3f4f6;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            appearance: none;
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .note {
            margin-top: 14px;
            font-size: 13px;
            color: #6b7280;
        }

        .status {
            margin-top: 14px;
            font-size: 14px;
            font-weight: 600;
        }

        .status.error {
            color: #b91c1c;
        }

        .status.ok {
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <h1>Compartir hecho</h1>
            </div>

            <div class="body">
                <div class="texto" id="textoCompartir">{{ $message ?? '' }}</div>

                @if(!empty($media) && count($media))
                    <div class="media-grid">
                        @foreach($media as $img)
                            <img src="{{ $img }}" alt="evidencia">
                        @endforeach
                    </div>
                @endif

                <div class="actions">
                    <button type="button" class="btn btn-primary" id="btnCompartir">
                        Compartir
                    </button>

                    <button type="button" class="btn btn-secondary" id="btnCerrar">
                        Cerrar
                    </button>
                </div>

                <div class="status" id="status"></div>

                <div class="note">
                    Esta pantalla abrirá el compartir nativo con el mismo flujo web.
                </div>
            </div>
        </div>
    </div>

    <script>
        const texto = @json(trim($message ?? ''));
        const fotos = @json(array_values($media ?? []));
        const statusEl = document.getElementById('status');
        const btnCompartir = document.getElementById('btnCompartir');
        const btnCerrar = document.getElementById('btnCerrar');

        function setStatus(msg, isError = false) {
            statusEl.textContent = msg || '';
            statusEl.className = 'status' + (isError ? ' error' : ' ok');
        }

        async function descargarArchivos(urls) {
            const archivos = [];

            for (let i = 0; i < urls.length; i++) {
                const url = urls[i];

                try {
                    const resp = await fetch(url);
                    if (!resp.ok) continue;

                    const blob = await resp.blob();
                    const mime = blob.type || 'image/jpeg';
                    let ext = 'jpg';

                    if (mime === 'image/png') {
                        ext = 'png';
                    } else if (mime === 'image/webp') {
                        ext = 'webp';
                    } else if (mime === 'image/jpeg') {
                        ext = 'jpg';
                    }

                    archivos.push(new File([blob], 'hecho_' + (i + 1) + '.' + ext, {
                        type: mime
                    }));
                } catch (e) {
                }
            }

            return archivos;
        }

        async function compartir() {
            try {
                setStatus('Preparando compartir...');

                if (!navigator.share) {
                    throw new Error('Este navegador no soporta compartir nativo.');
                }

                const archivos = await descargarArchivos(fotos);

                if (archivos.length && navigator.canShare && navigator.canShare({ files: archivos })) {
                    await navigator.share({
                        text: texto,
                        files: archivos
                    });

                    setStatus('Compartido correctamente.');
                    return;
                }

                if (texto) {
                    await navigator.share({
                        text: texto
                    });

                    setStatus('Compartido correctamente.');
                    return;
                }

                throw new Error('No hay contenido para compartir.');
            } catch (e) {
                setStatus(e.message || 'No se pudo compartir.', true);
            }
        }

        btnCompartir.addEventListener('click', compartir);

        btnCerrar.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }

            window.close();
        });

        window.addEventListener('load', function () {
            setTimeout(() => {
                compartir();
            }, 250);
        });
    </script>
</body>
</html>
