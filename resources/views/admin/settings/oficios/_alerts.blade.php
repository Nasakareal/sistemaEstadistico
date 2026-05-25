<script>
    @if (session('success'))
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: @json(session('success')),
            showConfirmButton: false,
            timer: 2600
        });
    @endif

    @if ($errors->any())
        @php
            $errorHtml = '<ul style="text-align:left;margin-bottom:0;">' .
                implode('', array_map(fn($message) => '<li>' . e($message) . '</li>', $errors->all())) .
                '</ul>';
        @endphp
        Swal.fire({
            icon: 'error',
            title: 'Revisa el formulario',
            html: @json($errorHtml),
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>
