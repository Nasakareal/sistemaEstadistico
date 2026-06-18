<style>
    .form-group label {
        font-weight: bold;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        background-color: #12263c !important;
        border: 1px solid rgba(125, 178, 225, .45) !important;
        border-radius: .25rem;
        color: #f8fafc !important;
        padding: 2px 6px;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #64b5f6 !important;
        box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18);
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #2563d8 !important;
        border: 1px solid rgba(147, 197, 253, .55) !important;
        color: #ffffff !important;
        border-radius: 4px;
        margin-top: 4px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        color: #ffffff !important;
        padding-left: 4px;
        padding-right: 6px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        background-color: rgba(15, 23, 42, .28) !important;
        border-color: rgba(255, 255, 255, .18) !important;
        color: #ffffff !important;
        margin-right: 6px;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover,
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:focus {
        background-color: rgba(15, 23, 42, .45) !important;
        color: #ffffff !important;
    }

    .select2-container--default .select2-search--inline .select2-search__field {
        color: #ffffff !important;
        margin-top: 6px;
    }

    .select2-container--default .select2-search--inline .select2-search__field::placeholder {
        color: #cbd5e1 !important;
    }

    .select2-container--default .select2-dropdown {
        background-color: #12263c !important;
        border: 1px solid rgba(125, 178, 225, .45) !important;
        color: #f8fafc !important;
        box-shadow: 0 14px 38px rgba(0, 0, 0, .45);
    }

    .select2-container--default .select2-results__option {
        background-color: #12263c !important;
        color: #f8fafc !important;
    }

    .select2-container--default .select2-results__option--selected {
        background-color: #1e40af !important;
        color: #ffffff !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: #2563d8 !important;
        color: #ffffff !important;
    }

    .select2-container--default .select2-search--dropdown {
        background-color: #12263c !important;
        padding: 8px;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #0f1f33 !important;
        border: 1px solid rgba(125, 178, 225, .45) !important;
        border-radius: .25rem;
        color: #ffffff !important;
        outline: none;
    }

    .select2-container--open {
        z-index: 9999;
    }
</style>
