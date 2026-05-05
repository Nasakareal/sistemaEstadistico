<style>
    .hecho-whatsapp-preview-modal {
        background: #f8fafc;
        color: #111827;
    }

    .hecho-whatsapp-preview-modal .modal-header,
    .hecho-whatsapp-preview-modal .modal-footer {
        border-color: rgba(17, 24, 39, .12);
    }

    .hecho-whatsapp-preview-loading {
        border: 1px dashed rgba(17, 24, 39, .18);
        border-radius: 8px;
        color: #374151;
        font-weight: 700;
        padding: 18px;
        text-align: center;
    }

    .hecho-whatsapp-preview-text {
        background: #111827;
        border: 1px solid rgba(17, 24, 39, .16);
        border-radius: 8px;
        color: #f9fafb;
        font-family: Consolas, "Liberation Mono", Menlo, monospace;
        font-size: .92rem;
        line-height: 1.45;
        max-height: 58vh;
        overflow: auto;
        padding: 14px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .hecho-whatsapp-preview-fotos {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }

    .hecho-whatsapp-preview-foto {
        border: 1px solid rgba(17, 24, 39, .14);
        border-radius: 8px;
        display: block;
        overflow: hidden;
        background: #e5e7eb;
    }

    .hecho-whatsapp-preview-foto img {
        display: block;
        height: 150px;
        object-fit: cover;
        width: 100%;
    }
</style>
