{{-- Shared FAQ form fields. $faq is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Question &amp; Answer</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Question" name="question" value="{{ old('question', $faq->question ?? '') }}" required minlength="5" maxlength="255" placeholder="e.g. Is my donation tax-deductible?" :error="$errors->first('question')" />

        <div x-data="{ preview: false }">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="answer" class="block text-sm font-medium text-text-900 dark:text-night-text">Answer <span class="text-error-600" aria-hidden="true">*</span></label>
                <span class="text-xs text-text-400 dark:text-night-text-muted">Markdown supported: **bold**, *italic*, lists, [links](https://…)</span>
            </div>
            <textarea id="answer" name="answer" rows="8" minlength="10" maxlength="10000" required class="{{ $textareaClasses }}" aria-describedby="answer-help">{{ old('answer', $faq->answer ?? '') }}</textarea>
            @error('answer')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @else
                <p id="answer-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Raw HTML is stripped when the answer is displayed, so only Markdown formatting is shown on the website.</p>
            @enderror

            @if ($faq?->answer)
                <button type="button" @click="preview = ! preview" :aria-expanded="preview ? 'true' : 'false'" aria-controls="answer-preview" class="mt-3 text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                    <span x-text="preview ? 'Hide saved preview' : 'Show saved preview'">Show saved preview</span>
                </button>
                <div id="answer-preview" x-show="preview" x-cloak class="prose mt-2 rounded-md border border-border-subtle bg-surface-muted p-4 text-sm text-text-600 dark:border-night-border dark:bg-night-surface-alt dark:text-night-text-muted">
                    {!! $faq->answerHtml() !!}
                </div>
            @endif
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Organisation &amp; Publishing</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Category"
                name="faq_category_id"
                :options="['' => 'Uncategorised'] + $categories->pluck('name', 'id')->all()"
                :selected="old('faq_category_id', $faq->faq_category_id ?? '')"
                helper="Archived (inactive) categories hide their FAQs from the website."
                :error="$errors->first('faq_category_id')"
            />
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $faq->status?->value ?? 'draft')"
                :error="$errors->first('status')"
            />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Publish Date" name="published_at" type="datetime-local" value="{{ old('published_at', $faq?->published_at?->format('Y-m-d\TH:i')) }}" helper="Leave blank to use the moment it is published. A future date schedules it." :error="$errors->first('published_at')" />
            <x-ui.input label="Display Order" name="display_order" type="number" min="0" max="65535" value="{{ old('display_order', $faq->display_order ?? 0) }}" helper="Lower numbers appear first within a category." :error="$errors->first('display_order')" />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $faq->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this FAQ in the “Most Asked” section and on the homepage
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Internal Notes</h3>

    <div class="mt-4">
        <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Admin Notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="3" maxlength="2000" placeholder="Visible to the team only — never shown on the website. e.g. source of this answer, who to ask when it changes." class="{{ $textareaClasses }}">{{ old('admin_notes', $faq->admin_notes ?? '') }}</textarea>
        @error('admin_notes')
            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
        @enderror
    </div>
</x-ui.card>
