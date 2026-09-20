{{--
    Public analytics tags from Settings → Integrations. IDs are validated
    against strict patterns (G-XXXX / GTM-XXXX) before being stored, so
    they are safe to interpolate into script tags. Nothing renders when
    both are blank.
--}}
@php
    $gaId = setting('integrations.ga_measurement_id');
    $gtmId = setting('integrations.gtm_container_id');
@endphp
@if ($gtmId)
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtmId }}');</script>
@elseif ($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
@endif
