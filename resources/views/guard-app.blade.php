@php($branding = \App\Models\BrandingSetting::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="--brand-primary: {{ $branding->primary_color }}; --brand-light: {{ $branding->light_color }}; --brand-dark: {{ $branding->dark_color }};">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'RDI') }}</title>

        <link rel="icon" href="{{ $branding->faviconUrl() }}">
        <link rel="apple-touch-icon" href="{{ $branding->pwaIconUrl(192) }}">
        <link rel="manifest" href="{{ route('pwa.manifest', absolute: false) }}">
        <meta name="theme-color" content="{{ $branding->dark_color }}">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ \App\Models\BrandingSetting::PwaAppName }}">
        <meta name="application-name" content="{{ \App\Models\BrandingSetting::PwaAppName }}">

        @fonts

        @viteReactRefresh
        @vite(['resources/js/guard-app/main.tsx'])
    </head>
    <body class="font-sans antialiased">
        <div id="guard-app" data-logo-url="{{ $branding->logoUrl() }}"></div>
    </body>
</html>
