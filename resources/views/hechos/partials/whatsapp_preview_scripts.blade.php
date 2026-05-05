<script>
    (function () {
        const modal = document.getElementById('hechoWhatsAppPreviewModal');

        if (!modal) {
            return;
        }

        const loading = document.getElementById('hechoWhatsAppPreviewLoading');
        const content = document.getElementById('hechoWhatsAppPreviewContent');
        const textBox = document.getElementById('hechoWhatsAppPreviewText');
        const fotosBox = document.getElementById('hechoWhatsAppPreviewFotos');
        const copyButton = document.getElementById('hechoWhatsAppPreviewCopy');

        const showModal = function () {
            if (window.jQuery && jQuery.fn.modal) {
                jQuery(modal).modal('show');
            }
        };

        const setLoading = function () {
            loading.textContent = 'Cargando tarjeta...';
            loading.classList.remove('d-none');
            content.classList.add('d-none');
            textBox.textContent = '';
            fotosBox.innerHTML = '';
            copyButton.disabled = true;
        };

        const setContent = function (data) {
            const texto = (data.texto || '').trim();
            const fotos = Array.isArray(data.fotos) ? data.fotos.filter(Boolean) : [];

            textBox.textContent = texto || 'Sin texto disponible.';
            fotosBox.innerHTML = '';

            fotos.forEach(function (foto) {
                const link = document.createElement('a');
                link.className = 'hecho-whatsapp-preview-foto';
                link.href = foto;
                link.target = '_blank';
                link.rel = 'noopener';

                const img = document.createElement('img');
                img.src = foto;
                img.alt = 'Foto de la tarjeta WhatsApp';
                img.loading = 'lazy';

                link.appendChild(img);
                fotosBox.appendChild(link);
            });

            fotosBox.classList.toggle('d-none', fotos.length === 0);
            loading.classList.add('d-none');
            content.classList.remove('d-none');
            copyButton.disabled = texto === '';
        };

        const copyText = async function () {
            const texto = textBox.textContent || '';

            if (!texto.trim()) {
                return;
            }

            try {
                await navigator.clipboard.writeText(texto);
                copyButton.innerHTML = '<i class="fa-solid fa-check"></i> Copiado';

                setTimeout(function () {
                    copyButton.innerHTML = '<i class="fa-regular fa-copy"></i> Copiar texto';
                }, 1600);
            } catch (error) {
                const area = document.createElement('textarea');
                area.value = texto;
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
            }
        };

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('.btn-preview-whatsapp-card');

            if (!button) {
                return;
            }

            event.preventDefault();
            setLoading();
            showModal();

            try {
                const resp = await fetch(button.dataset.url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!resp.ok) {
                    throw new Error('No se pudo obtener la tarjeta.');
                }

                setContent(await resp.json());
            } catch (error) {
                loading.textContent = error.message || 'No se pudo obtener la tarjeta.';
                copyButton.disabled = true;
            }
        });

        copyButton.addEventListener('click', copyText);
    })();
</script>
