<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>

<body>
    <!-- Barra de progresso do Inertia -->
    <div id="inertia-progress-bar" class="hidden fixed top-0 left-0 right-0 h-1 bg-primary-600 z-50 transition-all duration-200">
        <div class="h-full bg-primary-700 animate-pulse"></div>
    </div>

    @inertia
</body>

</html>