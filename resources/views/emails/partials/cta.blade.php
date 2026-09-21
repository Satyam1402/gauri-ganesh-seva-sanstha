{{-- Bulletproof button: @include('emails.partials.cta', ['url' => ..., 'label' => ...]) --}}
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:{{ $margin ?? '24px 0 0' }};">
    <tr>
        <td style="border-radius:6px; background-color:{{ $brand['primaryColor'] }};">
            <a href="{{ $url }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
