@extends('emails.layout')

{{-- Generic internal alert used by any AdminNotification without a richer template of its own. --}}

@section('body')
    <p style="margin:0 0 8px; font-size:12px; font-weight:bold; letter-spacing:0.04em; text-transform:uppercase; color:#8c8577;">{{ $category->label() }}</p>

    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">{{ $title }}</h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6; white-space:pre-line;">{{ $body }}</p>

    @if ($url)
        @include('emails.partials.cta', ['url' => $url, 'label' => $actionLabel, 'margin' => '0'])
    @endif
@endsection
