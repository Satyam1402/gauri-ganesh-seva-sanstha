{{--
    Renders one settings field from its registry definition.
    $key, $field (type/label/help/options/rules), $value (current value).
--}}
@php
    $id = 'setting-'.$key;
    $label = $field['label'];
    $help = $field['help'] ?? null;
    $error = $errors->first($key);
    $required = in_array('required', $field['rules'] ?? [], true);
    $inputClasses = 'block w-full rounded-md border bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:bg-night-surface dark:text-night-text '.($error ? 'border-error-600' : 'border-border-subtle dark:border-night-border');
@endphp

@switch($field['type'])
    @case('textarea')
    @case('markdown')
        <div>
            <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">{{ $label }}@if ($required) <span class="text-error-600" aria-hidden="true">*</span>@endif</label>
            <textarea id="{{ $id }}" name="{{ $key }}" rows="{{ $field['type'] === 'markdown' ? 12 : 3 }}" class="{{ $inputClasses }} {{ $field['type'] === 'markdown' ? 'font-mono text-sm' : '' }}" @if ($help) aria-describedby="{{ $id }}-help" @endif>{{ old($key, $value) }}</textarea>
            @if ($error)
                <p class="mt-1.5 text-xs text-error-600">{{ $error }}</p>
            @elseif ($help || $field['type'] === 'markdown')
                <p id="{{ $id }}-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ $help }}{{ $field['type'] === 'markdown' ? ' Markdown supported; raw HTML is stripped when displayed.' : '' }}</p>
            @endif
        </div>
        @break

    @case('select')
        @php $options = $field['options'] === 'timezones' ? array_combine($timezones, $timezones) : $field['options']; @endphp
        <x-ui.select :label="$label" :name="$key" :id="$id" :options="$options" :selected="old($key, $value)" :helper="$help" :error="$error" />
        @break

    @case('boolean')
        <div class="flex items-start gap-3 rounded-md border border-border-subtle bg-surface-muted px-4 py-3 dark:border-night-border dark:bg-night-surface-alt">
            <input type="hidden" name="{{ $key }}" value="0">
            <input id="{{ $id }}" type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $value)) class="mt-1 rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35" @if ($help) aria-describedby="{{ $id }}-help" @endif>
            <div>
                <label for="{{ $id }}" class="block text-sm font-medium text-text-900 dark:text-night-text">{{ $label }}</label>
                @if ($help)
                    <p id="{{ $id }}-help" class="mt-0.5 text-xs text-text-600 dark:text-night-text-muted">{{ $help }}</p>
                @endif
            </div>
        </div>
        @break

    @case('color')
        <div>
            <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">{{ $label }}</label>
            <div class="flex items-center gap-3" x-data="{ color: @js(old($key, $value) ?? '') }">
                <input type="color" :value="color || '#0F5C4E'" @input="color = $event.target.value" class="h-10 w-14 cursor-pointer rounded-md border border-border-subtle bg-surface-white p-1 dark:border-night-border" aria-label="Pick {{ $label }}">
                <input id="{{ $id }}" type="text" name="{{ $key }}" x-model="color" placeholder="#RRGGBB" maxlength="7" class="{{ $inputClasses }} max-w-[10rem] font-mono uppercase" @if ($help) aria-describedby="{{ $id }}-help" @endif>
                <button type="button" @click="color = ''" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted">Reset</button>
            </div>
            @if ($error)
                <p class="mt-1.5 text-xs text-error-600">{{ $error }}</p>
            @elseif ($help)
                <p id="{{ $id }}-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ $help }}</p>
            @endif
        </div>
        @break

    @case('image')
    @case('file')
        @php $current = is_array($value) ? $value : null; @endphp
        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">{{ $label }}</p>
            @if ($current)
                <div class="mb-2 flex items-center gap-4">
                    <div class="flex h-20 w-40 items-center justify-center rounded-md border border-border-subtle bg-surface-muted p-2 dark:border-night-border dark:bg-night-surface-alt">
                        <img src="{{ $current['webp'] ?? $current['url'] }}" alt="Current {{ $label }}" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="text-xs text-text-600 dark:text-night-text-muted">
                        <p class="break-all">{{ $current['name'] }}</p>
                        <label class="mt-2 flex items-center gap-2">
                            <input type="checkbox" name="remove_{{ $key }}" value="1" class="rounded border-border-subtle text-error-600">
                            Remove
                        </label>
                    </div>
                </div>
            @endif
            <label for="{{ $id }}" class="sr-only">{{ $current ? 'Replace' : 'Upload' }} {{ $label }}</label>
            <input id="{{ $id }}" type="file" name="{{ $key }}" accept="{{ $field['type'] === 'file' ? 'image/png,image/x-icon,image/vnd.microsoft.icon,.ico' : 'image/png,image/jpeg,image/webp' }}" class="block w-full text-sm text-text-600 dark:text-night-text-muted" aria-describedby="{{ $id }}-help">
            <p id="{{ $id }}-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ $help }} {{ $field['type'] === 'file' ? 'PNG or ICO up to 512 KB.' : 'PNG, JPG or WebP up to 2 MB.' }}{{ $current ? ' Uploading a new file replaces the current one.' : '' }}</p>
            @if ($error)
                <p class="mt-1.5 text-xs text-error-600">{{ $error }}</p>
            @endif
        </div>
        @break

    @case('datetime')
        <x-ui.input :label="$label" :name="$key" :id="$id" type="datetime-local" :value="old($key, $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\TH:i') : '')" :helper="$help" :error="$error" />
        @break

    @case('date')
        <x-ui.input :label="$label" :name="$key" :id="$id" type="date" :value="old($key, $value)" :helper="$help" :error="$error" />
        @break

    @case('integer')
    @case('decimal')
        <x-ui.input :label="$label" :name="$key" :id="$id" type="number" :step="$field['type'] === 'decimal' ? '0.01' : '1'" :value="old($key, $value)" :helper="$help" :error="$error" :required="$required" class="max-w-xs" />
        @break

    @default
        @php $inputType = in_array($field['type'], ['email', 'url', 'tel'], true) ? $field['type'] : 'text'; @endphp
        <x-ui.input :label="$label" :name="$key" :id="$id" :type="$inputType" :value="old($key, $value)" :helper="$help" :error="$error" :required="$required" :placeholder="$inputType === 'url' ? 'https://' : null" />
@endswitch
