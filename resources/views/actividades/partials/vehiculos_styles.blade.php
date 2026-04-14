.vehiculo-total-badge {
    font-size: .9rem;
    padding: .4rem .6rem;
}

.vehiculos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 14px;
}

.vehiculo-card {
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255,255,255,.04);
    box-shadow: 0 10px 24px rgba(0,0,0,.18);
}

.vehiculo-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.05);
}

.min-w-0 {
    min-width: 0;
}

.vehiculo-title {
    color: #eaf0ff;
    font-weight: 800;
    font-size: 1rem;
    line-height: 1.15;
}

.vehiculo-subtitle {
    color: rgba(234,240,255,.68);
    font-weight: 600;
    font-size: .86rem;
    margin-top: 4px;
}

.vehiculo-placa {
    color: #0f172a;
    background: #f8fafc;
    border-radius: 8px;
    padding: .35rem .55rem;
    font-weight: 800;
    font-size: .82rem;
    white-space: nowrap;
}

.vehiculo-card-body {
    padding: 12px;
}

.vehiculo-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.vehiculo-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #eaf0ff;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 8px;
    padding: .35rem .55rem;
    font-weight: 700;
    font-size: .84rem;
}

.vehiculo-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.vehiculo-mini-grid div,
.vehiculo-nota {
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(0,0,0,.14);
    padding: 8px 10px;
    min-width: 0;
}

.vehiculo-mini-grid span,
.vehiculo-nota span {
    display: block;
    color: rgba(234,240,255,.62);
    font-size: .76rem;
    font-weight: 800;
    text-transform: uppercase;
}

.vehiculo-mini-grid strong {
    display: block;
    color: #eaf0ff;
    font-size: .9rem;
    line-height: 1.25;
    word-break: break-word;
}

.vehiculo-nota {
    margin-top: 8px;
}

.vehiculo-nota p {
    color: #eaf0ff;
    margin: 4px 0 0;
    white-space: pre-wrap;
    word-break: break-word;
}

.vehiculo-card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}

.modal-actividad-vehiculo {
    z-index: 2055 !important;
}

.modal-actividad-vehiculo .modal-content {
    color: #eaf0ff;
    background: #111827 !important;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,.65);
    opacity: 1 !important;
}

.modal-actividad-vehiculo .modal-header,
.modal-actividad-vehiculo .modal-footer {
    border-color: rgba(255,255,255,.12);
    background: #0b1220 !important;
}

.modal-actividad-vehiculo .modal-body {
    background: #111827 !important;
}

.modal-backdrop.show {
    opacity: .75;
    z-index: 2050;
}

.modal-actividad-vehiculo .close {
    color: #eaf0ff;
    opacity: .9;
    text-shadow: none;
}

.modal-subtitle {
    color: rgba(234,240,255,.68);
    font-size: .9rem;
    font-weight: 600;
    margin-top: 2px;
}

.modal-actividad-vehiculo label {
    color: #eaf0ff;
    font-weight: 800;
}

.modal-actividad-vehiculo .form-control,
.modal-actividad-vehiculo select.form-control,
.modal-actividad-vehiculo textarea.form-control {
    color: #eaf0ff;
    background-color: #111827;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 8px;
}

.modal-actividad-vehiculo .form-control::placeholder,
.modal-actividad-vehiculo textarea.form-control::placeholder {
    color: rgba(234,240,255,.45);
}

.modal-actividad-vehiculo select option {
    color: #111 !important;
    background: #fff !important;
}

.vehiculo-section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 4px 0 12px;
    color: #eaf0ff;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
    font-size: .82rem;
}

.vehiculo-section-title::after {
    content: "";
    height: 1px;
    flex: 1;
    background: rgba(255,255,255,.12);
}
