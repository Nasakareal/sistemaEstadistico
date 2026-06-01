<style>
    .liberacion-card {
        background: linear-gradient(135deg, rgba(10, 25, 47, .92), rgba(22, 34, 70, .90));
        border: 1px solid rgba(125, 178, 225, .24);
        box-shadow: 0 18px 48px rgba(2, 8, 23, .28);
        color: #f8fafc;
        overflow: hidden;
    }

    .liberacion-card .card-header {
        background: rgba(18, 38, 60, .88);
        border-bottom: 1px solid rgba(125, 178, 225, .22);
        color: #f8fafc;
    }

    .liberacion-card .card-title {
        color: #f8fafc;
        font-weight: 800;
        letter-spacing: 0;
    }

    .liberacion-card hr {
        border-top-color: rgba(255, 255, 255, .10);
    }

    .liberacion-form label {
        color: #e2e8f0 !important;
        font-weight: 800;
        letter-spacing: 0;
    }

    .liberacion-form .form-control {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        border: 1px solid rgba(125, 178, 225, .45) !important;
        border-radius: 8px;
        box-shadow: none !important;
        font-weight: 600;
    }

    .liberacion-form .form-control:focus {
        background-color: #ffffff !important;
        border-color: #64b5f6 !important;
        box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18) !important;
        color: #0f172a !important;
    }

    .liberacion-form .form-control[readonly] {
        background-color: #e5edf7 !important;
        color: #1e293b !important;
    }

    .liberacion-form select.form-control option {
        background-color: #12263c !important;
        color: #f8fafc !important;
    }

    .liberacion-form select.form-control option:checked {
        background-color: #2563d8 !important;
        color: #ffffff !important;
    }

    .liberacion-form .form-control::placeholder {
        color: #64748b !important;
        opacity: 1;
    }

    .liberacion-otro-wrap {
        background: rgba(255, 255, 255, .055);
        border: 1px solid rgba(100, 181, 246, .22);
        border-left: 4px solid #64b5f6;
        border-radius: 8px;
        margin-top: .75rem;
        padding: .9rem 1rem;
    }

    .liberacion-help {
        color: #cbd5e1;
        font-size: .85rem;
        margin-top: .25rem;
    }

    .liberacion-form .invalid-feedback {
        color: #fecaca;
        font-weight: 700;
    }

    .liberacion-form .btn-primary,
    .liberacion-form .btn-warning {
        background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important;
        border: 1px solid rgba(147, 197, 253, .35) !important;
        color: #ffffff !important;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(37, 99, 235, .22);
    }

    .liberacion-form .btn-primary:hover,
    .liberacion-form .btn-warning:hover {
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
        color: #ffffff !important;
    }

    .liberacion-form .btn-secondary {
        background-color: #64748b !important;
        border-color: #64748b !important;
        color: #ffffff !important;
        font-weight: 800;
    }

    .liberacion-form .alert-info {
        background: rgba(14, 165, 233, .12);
        border-color: rgba(125, 211, 252, .25);
        color: #e0f2fe;
    }
</style>
