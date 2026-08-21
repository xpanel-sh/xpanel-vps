<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XPanel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-white text-black">
    <main class="flex min-h-screen items-center justify-center p-6">
        <section class="max-w-2xl text-center">
            <p class="text-sm font-bold uppercase tracking-[0.3em] text-gray-500">XPanel</p>
            <h1 class="mt-4 text-5xl font-black tracking-tight">Hosting bajo tu control</h1>
            <p class="mt-5 text-gray-500">
                Usa el acceso admin para operar la plataforma o el acceso cliente para gestionar recursos asignados.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('admin.login') }}" class="rounded-2xl bg-black px-6 py-4 font-bold text-white">Admin</a>
                <a href="{{ route('client.login') }}" class="rounded-2xl border border-black/10 px-6 py-4 font-bold">Cliente</a>
            </div>
        </section>
    </main>
</body>

</html>
