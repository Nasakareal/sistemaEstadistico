.form-group.sv-field-filled label::after {
    content: 'Capturado';
    display: inline-block;
    margin-left: 8px;
    padding: 2px 7px;
    border-radius: 999px;
    color: #06151f;
    background: #7dd3fc;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0;
    text-transform: uppercase;
}

.form-group.sv-field-filled .form-control,
.form-group.sv-field-filled select.form-control,
.form-group.sv-field-filled textarea.form-control {
    border-color: rgba(125, 211, 252, .9);
    background-color: rgba(14, 116, 144, .26);
    box-shadow: 0 0 0 1px rgba(125, 211, 252, .16);
}

.form-group.sv-field-review label::after {
    content: 'Revisar';
    background: #fbbf24;
}

.form-group.sv-field-review .form-control {
    border-color: rgba(251, 191, 36, .95);
    background-color: rgba(120, 53, 15, .28);
}
