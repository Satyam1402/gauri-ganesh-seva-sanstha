{{-- Small key/value table for a breakdown: $rows [label => number|['count'=>,'amount'=>]], $title, $money. --}}
@php $money ??= false; $columns ??= null; @endphp
<div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <caption class="sr-only">{{ $title }}</caption>
        <thead class="border-b border-border-subtle text-xs uppercase tracking-wide text-text-400 dark:border-night-border dark:text-night-text-muted">
            <tr>
                <th scope="col" class="py-2 pr-4 font-medium">{{ $columns[0] ?? 'Item' }}</th>
                <th scope="col" class="py-2 pr-4 text-right font-medium">{{ $columns[1] ?? 'Count' }}</th>
                @if (is_array(reset($rows)) && array_key_exists('amount', reset($rows)))
                    <th scope="col" class="py-2 text-right font-medium">{{ $columns[2] ?? 'Amount' }}</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-border-subtle dark:divide-night-border">
            @forelse ($rows as $label => $value)
                <tr>
                    <th scope="row" class="py-2 pr-4 font-normal text-text-900 dark:text-night-text">{{ $label }}</th>
                    @if (is_array($value))
                        <td class="py-2 pr-4 text-right text-text-600 dark:text-night-text-muted">{{ number_format($value['count']) }}</td>
                        <td class="py-2 text-right font-medium text-text-900 dark:text-night-text">{{ format_inr($value['amount']) }}</td>
                    @else
                        <td class="py-2 pr-4 text-right font-medium text-text-900 dark:text-night-text">{{ $money ? format_inr((float) $value) : number_format((float) $value) }}</td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="3" class="py-6 text-center text-sm text-text-400 dark:text-night-text-muted">Nothing recorded for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
