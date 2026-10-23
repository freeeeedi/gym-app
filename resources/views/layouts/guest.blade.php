<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#09090b">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="icon" href="/icons/icon.svg" type="image/svg+xml">

        <title>{{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-zinc-950 font-sans text-zinc-100 antialiased">
        <div class="flex min-h-screen flex-col items-center px-4 pt-10 sm:justify-center sm:pt-0">
            <a href="/" class="flex flex-col items-center gap-2">
                <x-application-logo class="h-16 w-16 text-lime-400" />
                <span class="text-lg font-bold">{{ config('app.name') }}</span>
            </a>

            <div class="mt-6 w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 px-5 py-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
