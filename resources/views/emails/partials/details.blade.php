{{-- Key/value table: @include('emails.partials.details', ['rows' => ['Label' => 'Value', ...]]). Null/empty values are skipped. --}}
@php
    $rows = array_filter($rows, fn ($value) => $value !== null && $value !== '');
@endphp
@if ($rows !== [])
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:{{ $margin ?? '0 0 24px' }}; border:1px solid #e5ded2; border-radius:6px; font-size:14px;">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="padding:10px 16px; color:#8c8577; width:42%; vertical-align:top; {{ $loop->last ? '' : 'border-bottom:1px solid #e5ded2;' }}">{{ $label }}</td>
                <td style="padding:10px 16px; vertical-align:top; {{ $loop->last ? '' : 'border-bottom:1px solid #e5ded2;' }}">{!! $value instanceof \Illuminate\Support\HtmlString ? $value : e($value) !!}</td>
            </tr>
        @endforeach
    </table>
@endif
