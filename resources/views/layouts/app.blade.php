<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#09090b">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <title>{{ isset($title) ? $title.' — ' : '' }}{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-zinc-950 font-sans text-zinc-100 antialiased">
        @isset($header)
            <header class="sticky top-0 z-20 border-b border-zinc-900 bg-zinc-950/90 backdrop-blur">
                <div class="mx-auto flex max-w-xl items-center gap-3 px-4 py-3">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="mx-auto max-w-xl px-4 pb-28 pt-4">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-lime-900 bg-lime-950/60 px-4 py-3 text-sm text-lime-200" role="status">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any() && ! request()->routeIs("settings.*", "profile.*"))
                <div class="mb-4 rounded-xl border border-red-900 bg-red-950/60 px-4 py-3 text-sm text-red-200">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{ $slot }}
        </main>

        <x-bottom-nav />

        @livewireScripts
        @stack('scripts')
    </body>
</html>
