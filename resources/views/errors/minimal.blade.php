{{--
    Standalone error page layout. Overrides the framework's errors::minimal, so Laravel's own
    401/402/429/503 pages get the same look. It must not touch the DB, session or $store
    (it also renders the 500 page), so it only uses config('app.name').
    Sections: title, code, message, optional description and icon (x-icon name).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>@yield('title') · {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-full items-center justify-center bg-slate-100 px-4 py-12 font-sans text-slate-800 antialiased">
        <main class="w-full max-w-lg text-center">
            <div class="card relative overflow-hidden px-6 py-10 sm:px-10">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary-400 via-primary-600 to-teal-500" aria-hidden="true"></div>

                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-700">
                    <x-icon :name="trim($__env->yieldContent('icon', 'exclamation-triangle'))" class="h-8 w-8" />
                </span>

                <p class="mt-6 text-6xl font-extrabold tracking-tight text-primary-600">@yield('code')</p>
                <h1 class="mt-3 text-xl font-bold text-slate-900 sm:text-2xl">@yield('message')</h1>
                @hasSection('description')
                    <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-600">@yield('description')</p>
                @endif

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <x-icon name="home" class="h-4 w-4" />
                        Kembali ke beranda
                    </a>
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                        Halaman sebelumnya
                    </button>
                </div>
            </div>

            <p class="mt-6 text-xs text-slate-500">{{ config('app.name') }}</p>
        </main>
    </body>
</html>
