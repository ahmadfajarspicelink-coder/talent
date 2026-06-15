<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts: Inter (NovaSpark) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-nova-light">
            <div class="flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 3L4 14h6l-1 7 9-11h-6l1-7z" />
                    </svg>
                </span>
                <span class="text-xl font-bold tracking-tight text-nova-dark">{{ config('app.name', 'Laravel') }}</span>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white border border-gray-200 shadow-nova-md overflow-hidden rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
