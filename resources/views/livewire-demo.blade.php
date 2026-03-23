<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Livewire Demo</title>

    @vite(['resources/css/app.css'])
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto w-full max-w-2xl px-6 py-16">
        <div class="card shadow-sm p-8">
            <h1 class="mb-2 text-2xl font-semibold">Demo Livewire 4</h1>
            <p class="mb-8 text-sm text-slate-600">Esta pantalla confirma que Livewire esta activo en el proyecto.</p>

            <livewire:demo-counter />

            <a href="{{ route('home') }}" class="mt-8 inline-block btn-secondary text-sm">Volver al Home de Inertia</a>
        </div>
    </main>

    @livewireScripts
</body>

</html>
