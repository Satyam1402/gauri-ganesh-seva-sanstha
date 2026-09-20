{{--
    The single place metadata is rendered. $seo is an App\Support\Seo\SeoData
    built by SeoService in the controller; the layout falls back to site
    defaults when a view provides none. Views never emit meta tags.
--}}
@php
    $siteName = setting('general.site_name', config('app.name'));
    $twitterSite = setting('seo.twitter_site');
@endphp
<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<meta name="robots" content="{{ $seo->robots }}">
@if ($seo->keywords)
    <meta name="keywords" content="{{ $seo->keywords }}">
@endif
<link rel="canonical" href="{{ $seo->canonical }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->ogTitle }}">
<meta property="og:description" content="{{ $seo->ogDescription }}">
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}_IN">
@if ($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
@endif
@if ($seo->ogType === 'article')
    @if ($seo->publishedTime)
        <meta property="article:published_time" content="{{ $seo->publishedTime }}">
    @endif
    @if ($seo->modifiedTime)
        <meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
    @endif
@endif

<meta name="twitter:card" content="{{ $seo->twitterCard }}">
@if ($twitterSite)
    <meta name="twitter:site" content="{{ str_starts_with($twitterSite, '@') ? $twitterSite : '@'.$twitterSite }}">
@endif
<meta name="twitter:title" content="{{ $seo->twitterTitle }}">
<meta name="twitter:description" content="{{ $seo->twitterDescription }}">
@if ($seo->twitterImage)
    <meta name="twitter:image" content="{{ $seo->twitterImage }}">
@endif

@if ($verification = setting('seo.google_site_verification'))
    <meta name="google-site-verification" content="{{ $verification }}">
@endif
@if ($verification = setting('seo.bing_site_verification'))
    <meta name="msvalidate.01" content="{{ $verification }}">
@endif

@foreach ($seo->schemas as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach
@stack('structured_data')
