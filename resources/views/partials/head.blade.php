<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="description" content="{{ ($metaDescription ?? null) ?: 'ProoDev turns your real work into evidence. AI analyzes repositories and projects into engineering reports and an explainable Engineering Magnitude score.' }}">
<meta name="keywords" content="{{ ($metaKeywords ?? null) ?: app(\App\Services\SiteSettings::class)->metaKeywords() }}">
<meta name="robots" content="index, follow">

<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'Laravel') }}">
<meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
<meta property="og:description" content="{{ $metaDescription ?? 'ProoDev turns your real work into evidence. AI analyzes repositories and projects into engineering reports and an explainable Engineering Magnitude score.' }}">
<meta property="og:url" content="{{ url()->current() }}">

<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
<meta name="twitter:description" content="{{ $metaDescription ?? 'ProoDev turns your real work into evidence. AI analyzes repositories and projects into engineering reports and an explainable Engineering Magnitude score.' }}">

<link rel="icon" href="/images/favicon-128.png" sizes="128x128" type="image/png">
<link rel="icon" href="/images/favicon-64.png" sizes="64x64" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
