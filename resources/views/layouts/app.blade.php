<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>RRHH Relojbiometrico</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" href="{{ asset('images/hu_icon.png') }}" type="image/x-icon">


    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/fontawesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/solid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/css/brands.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>

        <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap Select CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <!-- Bootstrap Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

    <!-- Font Awesome JS -->
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.0/js/fontawesome.min.js"></script>

    <style>
        .custom-tooltip {
            position: relative;
        }
        
        .custom-tooltip::before {
            content: attr(data-tooltip_text); /* Usar el contenido del atributo */
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
            z-index: 1000;
        }
        
        .custom-tooltip:hover::before {
            opacity: 1;
        }

        thead th:first-child {
            border-top-left-radius: 0.75rem; /* Esquina superior izquierda */
        }

        thead th:last-child {
            border-top-right-radius: 0.75rem; /* Esquina superior derecha */
        }

        tbody tr:last-child td:first-child {
            border-bottom-left-radius: 0.75rem; /* Esquina inferior izquierda */
        }

        tbody tr:last-child td:last-child {
            border-bottom-right-radius: 0.75rem; /* Esquina inferior derecha */
        }

        .select_modal {
            width: 441px !important;
        }

        .select_modal_2 {
            width: 100% !important;
        }

        .bootstrap-select .dropdown-toggle {
            width: 100% !important; /* Asegurar que el botón del select también lo respete */
            background-color: white !important;
            border-color: rgb(209 213 219 / var(--tw-border-opacity, 1));
            border-radius: 0.75rem;
            --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --tw-shadow-colored: 0 1px 2px 0 var(--tw-shadow-color);
            box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
        }

        .bootstrap-select .dropdown-menu {
            border-radius: 0.75rem !important;
        }

        .bootstrap-select .bs-searchbox input {
            border-radius: 0.75rem !important;
            padding: 0.5rem; /* Opcional: mejora la apariencia */
        }
        </style>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header> @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
<script>
    function custom_alert(message, type) {
        if (type == 'success') {
            $('#success-alert').html(message);
            $('#success-alert').show();
        } else if (type == 'error') {
            $('#error-alert').html(message);
            $('#error-alert').show();
        }
    }
</script>


  
