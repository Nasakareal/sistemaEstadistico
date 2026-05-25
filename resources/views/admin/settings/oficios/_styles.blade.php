<style>
    .oficio-numero {
        font-family: Consolas, Monaco, monospace;
        letter-spacing: 0;
    }

    .oficio-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 5px 10px;
        font-weight: 800;
        font-size: 12px;
        white-space: nowrap;
    }

    .oficio-badge--entrada {
        background: rgba(40, 167, 69, .16);
        color: #7CFF9C;
        border: 1px solid rgba(40, 167, 69, .35);
    }

    .oficio-badge--salida {
        background: rgba(23, 162, 184, .16);
        color: #74E7FF;
        border: 1px solid rgba(23, 162, 184, .35);
    }

    .oficio-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .oficio-meta__item {
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 8px;
        padding: 12px;
        background: rgba(255,255,255,.04);
    }

    .oficio-meta__label {
        color: rgba(255,255,255,.58);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .oficio-meta__value {
        margin-top: 4px;
        font-weight: 700;
        word-break: break-word;
    }

    .oficio-fotos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: 10px;
    }

    .oficio-foto {
        display: block;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px;
        padding: 8px;
        background: rgba(255,255,255,.04);
        cursor: pointer;
    }

    .oficio-foto img {
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        border-radius: 6px;
        display: block;
    }

    .oficio-foto span {
        display: flex;
        gap: 6px;
        align-items: center;
        margin-top: 6px;
        font-size: 12px;
        font-weight: 700;
    }

    select.form-control,
    select.custom-select {
        background-color: #12263c;
        color: #f8fafc;
        border-color: rgba(125, 178, 225, .45);
    }

    select.form-control:focus,
    select.custom-select:focus {
        background-color: #12263c;
        color: #ffffff;
        border-color: #64b5f6;
        box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18);
    }

    select.form-control option,
    select.custom-select option {
        background-color: #12263c;
        color: #f8fafc;
    }

    select.form-control option:checked,
    select.custom-select option:checked {
        background-color: #2563d8;
        color: #ffffff;
    }

    select.form-control option:disabled,
    select.custom-select option:disabled {
        color: #94a3b8;
    }

    .table td {
        vertical-align: middle;
    }

    .oficio-row--pendiente-contestacion > td {
        background: rgba(220, 53, 69, .18) !important;
    }

    .oficio-row--pendiente-contestacion:hover > td {
        background: rgba(220, 53, 69, .26) !important;
    }
</style>
