<div class="modal fade" id="hechoWhatsAppPreviewModal" tabindex="-1" role="dialog" aria-labelledby="hechoWhatsAppPreviewTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content hecho-whatsapp-preview-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="hechoWhatsAppPreviewTitle">
                    <i class="fa-brands fa-whatsapp"></i> Tarjeta WhatsApp
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div id="hechoWhatsAppPreviewLoading" class="hecho-whatsapp-preview-loading">
                    Cargando tarjeta...
                </div>

                <div id="hechoWhatsAppPreviewContent" class="d-none">
                    <div id="hechoWhatsAppPreviewFotos" class="hecho-whatsapp-preview-fotos mb-3"></div>
                    <pre id="hechoWhatsAppPreviewText" class="hecho-whatsapp-preview-text mb-0"></pre>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="hechoWhatsAppPreviewCopy">
                    <i class="fa-regular fa-copy"></i> Copiar texto
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
