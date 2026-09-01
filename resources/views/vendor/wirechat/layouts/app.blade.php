<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
@php
    $currentPanel= \Wirechat\Wirechat\Facades\Wirechat::currentPanel();
    $title = $currentPanel->getHeading()?? config('app.name', 'Laravel');
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <!-- Match the app theme (Flux appearance) to prevent flickering -->
    <script>
        function applyAppearance(appearance) {
            var applyDark = function () { document.documentElement.classList.add('dark'); document.documentElement.style.colorScheme = 'dark'; };
            var applyLight = function () { document.documentElement.classList.remove('dark'); document.documentElement.style.colorScheme = ''; };

            if (appearance === 'dark') {
                window.localStorage.setItem('flux.appearance', 'dark');
                applyDark();
            } else if (appearance === 'light') {
                window.localStorage.setItem('flux.appearance', 'light');
                applyLight();
            } else {
                window.localStorage.removeItem('flux.appearance');
                window.matchMedia('(prefers-color-scheme: dark)').matches ? applyDark() : applyLight();
            }
        }

        var storedAppearance = null;
        try { storedAppearance = window.localStorage.getItem('flux.appearance'); } catch (e) {}
        applyAppearance(storedAppearance || 'system');

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (event) {
            var stored = null;
            try { stored = window.localStorage.getItem('flux.appearance'); } catch (e) {}
            if (!stored || stored === 'system') {
                event.matches
                    ? document.documentElement.classList.add('dark')
                    : document.documentElement.classList.remove('dark');
            }
        });

        window.addEventListener('storage', function (event) {
            if (event.key === 'flux.appearance') applyAppearance(event.newValue || 'system');
        });
    </script>

    {{--Set up Favicon--}}
    @if($currentPanel->hasFavicon())
        <link rel="icon" href="{{ $currentPanel->getFavicon() }}" />
    @endif

    <!-- Fonts -->
    @fonts
    <!-- Scripts -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @wirechatStyles(panel: $panel)
</head>

<body  x-data x-cloak class="font-sans antialiased">
    <div class="min-h-screen bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)]">

        <!-- Page Content -->
        <main class="h-[calc(100vh_-_0.0rem)]">
             @yield('content',$slot??null)
        </main>

    </div>

    @livewireScripts
    @wirechatAssets(panel: $panel)

{{--    <script>--}}
{{--        document.addEventListener('livewire:updated', function () {--}}
{{--            document.querySelectorAll('img[src]').forEach(img => {--}}
{{--                const src = img.getAttribute('src');--}}
{{--                const svg = img.nextElementSibling;--}}
{{--                if (src) {--}}
{{--                    const preloadImg = new Image();--}}
{{--                    preloadImg.src = src;--}}
{{--                    preloadImg.onload = () => {--}}
{{--                        img.style.display = 'inline-flex';--}}
{{--                        svg.style.display = 'none';--}}
{{--                    };--}}
{{--                    preloadImg.onerror = () => {--}}
{{--                        img.style.display = 'none';--}}
{{--                        svg.style.display = 'inline-flex';--}}
{{--                    };--}}
{{--                } else {--}}
{{--                    img.style.display = 'none';--}}
{{--                    svg.style.display = 'inline-flex';--}}
{{--                }--}}
{{--            });--}}
{{--        });--}}
{{--    </script>--}}
</body>

</html>
