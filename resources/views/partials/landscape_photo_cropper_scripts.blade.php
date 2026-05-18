<script>
    (function () {
        if (window.SeguridadVialLandscapeCropper) return;

        const LANDSCAPE_ASPECT = 16 / 9;
        const MAX_WIDTH = 1600;
        const JPEG_QUALITY = .85;

        let modal;
        let modalState = null;

        function ensureModal() {
            if (modal) return modal;

            modal = document.createElement('div');
            modal.className = 'sv-crop-modal';
            modal.innerHTML = `
                <div class="sv-crop-dialog" role="dialog" aria-modal="true" aria-label="Elegir recorte">
                    <div class="sv-crop-header">Elegir recorte</div>
                    <div class="sv-crop-body">
                        <div class="sv-crop-stage">
                            <img alt="Foto para recortar">
                            <div class="sv-crop-selection"></div>
                        </div>
                        <div class="sv-crop-help">Toca o mueve el recuadro para elegir qué parte se guardará en horizontal.</div>
                        <input class="sv-crop-range" type="range" min="0" max="100" value="50">
                    </div>
                    <div class="sv-crop-footer">
                        <button type="button" class="btn btn-outline-light sv-crop-cancel">Cancelar</button>
                        <button type="button" class="btn btn-primary sv-crop-accept">Usar recorte</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            const stage = modal.querySelector('.sv-crop-stage');
            const range = modal.querySelector('.sv-crop-range');
            const cancel = modal.querySelector('.sv-crop-cancel');
            const accept = modal.querySelector('.sv-crop-accept');

            range.addEventListener('input', function () {
                setSelectionFraction(Number(range.value) / 100);
            });

            stage.addEventListener('pointerdown', function (event) {
                if (!modalState) return;
                event.preventDefault();
                stage.setPointerCapture(event.pointerId);
                setFractionFromPointer(event);
            });

            stage.addEventListener('pointermove', function (event) {
                if (!modalState || event.buttons !== 1) return;
                event.preventDefault();
                setFractionFromPointer(event);
            });

            cancel.addEventListener('click', function () {
                closeModal(null);
            });

            accept.addEventListener('click', function () {
                closeModal(modalState ? modalState.fraction : .5);
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal(null);
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal(null);
                }
            });

            return modal;
        }

        function setSelectionFraction(value) {
            if (!modalState) return;

            const fraction = Math.max(0, Math.min(1, Number.isFinite(value) ? value : .5));
            modalState.fraction = fraction;

            const selection = modal.querySelector('.sv-crop-selection');
            const range = modal.querySelector('.sv-crop-range');
            const cropHeightPercent = modalState.cropHeightPercent;
            const maxTopPercent = Math.max(0, 100 - cropHeightPercent);

            selection.style.top = (maxTopPercent * fraction) + '%';
            selection.style.height = cropHeightPercent + '%';
            range.value = String(Math.round(fraction * 100));
        }

        function setFractionFromPointer(event) {
            const stage = modal.querySelector('.sv-crop-stage');
            const rect = stage.getBoundingClientRect();
            const selectionHeight = rect.height * (modalState.cropHeightPercent / 100);
            const maxTop = Math.max(0, rect.height - selectionHeight);

            if (maxTop <= 0) {
                setSelectionFraction(.5);
                return;
            }

            const nextTop = Math.max(0, Math.min(maxTop, event.clientY - rect.top - (selectionHeight / 2)));
            setSelectionFraction(nextTop / maxTop);
        }

        function closeModal(result) {
            if (!modalState) return;

            const resolver = modalState.resolve;
            modalState = null;
            modal.classList.remove('is-open');
            resolver(result);
        }

        function askCropPosition(img, url) {
            ensureModal();

            const preview = modal.querySelector('.sv-crop-stage img');
            const cropHeightPercent = Math.min(
                100,
                ((img.naturalWidth / LANDSCAPE_ASPECT) / img.naturalHeight) * 100
            );

            preview.src = url;

            return new Promise(function (resolve) {
                modalState = {
                    resolve: resolve,
                    fraction: .5,
                    cropHeightPercent: cropHeightPercent,
                };

                modal.classList.add('is-open');
                window.setTimeout(function () {
                    setSelectionFraction(.5);
                }, 0);
            });
        }

        function loadImage(file) {
            return new Promise(function (resolve, reject) {
                const url = URL.createObjectURL(file);
                const img = new Image();

                img.onload = function () {
                    resolve({ img: img, url: url });
                };

                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    reject(new Error('No se pudo leer la imagen.'));
                };

                img.src = url;
            });
        }

        function canvasToBlob(canvas) {
            return new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    resolve(blob);
                }, 'image/jpeg', JPEG_QUALITY);
            });
        }

        function croppedName(name) {
            const clean = String(name || 'foto').replace(/\.[^.]+$/, '');
            return clean + '_horizontal.jpg';
        }

        async function cropFile(file) {
            if (!file || !file.type || !file.type.startsWith('image/')) {
                return file;
            }

            const loaded = await loadImage(file);
            const img = loaded.img;

            try {
                if (img.naturalWidth > img.naturalHeight) {
                    return file;
                }

                const fraction = await askCropPosition(img, loaded.url);
                if (fraction === null) {
                    return null;
                }

                const cropWidth = img.naturalWidth;
                const cropHeight = Math.min(
                    img.naturalHeight,
                    Math.max(1, Math.round(cropWidth / LANDSCAPE_ASPECT))
                );
                const maxCropY = Math.max(0, img.naturalHeight - cropHeight);
                const cropY = Math.round(maxCropY * fraction);
                const targetWidth = Math.min(MAX_WIDTH, cropWidth);
                const targetHeight = Math.round(targetWidth * cropHeight / cropWidth);
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                canvas.width = targetWidth;
                canvas.height = targetHeight;
                ctx.drawImage(img, 0, cropY, cropWidth, cropHeight, 0, 0, targetWidth, targetHeight);

                const blob = await canvasToBlob(canvas);
                if (!blob) return file;

                return new File([blob], croppedName(file.name), {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                });
            } finally {
                URL.revokeObjectURL(loaded.url);
            }
        }

        function setInputFiles(input, files) {
            const dataTransfer = new DataTransfer();
            files.forEach(function (file) {
                dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
        }

        function attach(input) {
            if (!input || input.dataset.svLandscapeCropperAttached === '1') return;
            input.dataset.svLandscapeCropperAttached = '1';

            input.addEventListener('change', async function (event) {
                if (input.dataset.svLandscapeCropperReady === '1') {
                    delete input.dataset.svLandscapeCropperReady;
                    return;
                }

                const files = Array.from(input.files || []);
                if (!files.length) return;

                event.preventDefault();
                event.stopImmediatePropagation();

                input.disabled = true;

                try {
                    const processed = [];
                    const limit = input.multiple ? files.length : 1;

                    for (const file of files.slice(0, limit)) {
                        const next = await cropFile(file);
                        if (next) processed.push(next);
                    }

                    setInputFiles(input, processed);
                    input.dataset.svLandscapeCropperReady = '1';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (error) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo preparar la foto',
                            text: error && error.message ? error.message : 'Intenta seleccionar la imagen nuevamente.',
                        });
                    } else {
                        alert('No se pudo preparar la foto. Intenta seleccionar la imagen nuevamente.');
                    }
                } finally {
                    input.disabled = false;
                }
            }, true);
        }

        window.SeguridadVialLandscapeCropper = { attach: attach };
    })();
</script>
