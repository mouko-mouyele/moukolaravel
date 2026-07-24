<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AutoChain">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.svg">
    <title>AutoChain Emma+ — Moïse</title>
    @php
        $cssFile = null;
        $jsFile = null;
        $manifestPath = public_path('build/manifest.json');
        if (is_readable($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
            $jsFile = $manifest['resources/js/app.js']['file'] ?? null;
        }
    @endphp
    @if ($cssFile && $jsFile)
        <link rel="stylesheet" href="{{ asset('build/'.$cssFile) }}">
        <script type="module" src="{{ asset('build/'.$jsFile) }}"></script>
    @endif
</head>
<body>
    <div id="app"></div>
    @if ($jsFile)
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
    @endif
</body>
</html>
