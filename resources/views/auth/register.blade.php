<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro Deshabilitado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <main class="max-w-md w-full text-center">
        <div class="text-4xl text-yellow-500 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="font-semibold text-xl text-gray-900 mb-2">Registro Deshabilitado</h1>
        <p class="text-gray-600 mb-6 text-sm leading-relaxed">
            Lo sentimos, el registro de nuevos usuarios se encuentra <br /> temporalmente deshabilitado.
        </p>
        <div class="border border-yellow-300 rounded-md bg-yellow-50 p-4 mb-6 text-left text-xs text-yellow-800">
            <div class="flex items-start mb-1">
                <i class="fas fa-exclamation-triangle mr-2 mt-[2px] text-xs"></i>
                <span class="font-semibold">Aviso Importante</span>
            </div>
            <p>
                El sistema de registro no está disponible en este momento.
                Por favor, contacte con el
                administrador.
            </p>
        </div>
        <a href="{{ route('login') }}"
            class="w-full bg-gray-900 text-white px-3 text-xs font-semibold py-2 rounded-xl mb-2 hover:bg-gray-800 transition">
            Ir a Iniciar Sesión
        </a>
    </main>
</body>

</html>
