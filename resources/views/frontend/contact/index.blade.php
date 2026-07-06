@extends('layouts.app')

@php
    $seo = $page?->seo;
    $pageUrl = route('contact');
    // SEO fallback chains deliberately end in string literals — an inline
    // @section with a null value leaks an unclosed output buffer.
    $metaDescription = $seo?->meta_description
        ?? 'Contact '.config('app.name').' — reach us by phone, email, WhatsApp, or the enquiry form for donations, volunteering, partnerships, and general questions.';
@endphp

@section('title', $seo?->meta_title ?? 'Contact Us — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $pageUrl)
@section('og_title', $seo?->og_title ?? 'Contact Us')
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage)
    @section('og_image', $seo->ogImage->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'ContactPage',
            'name' => $seo?->meta_title ?? 'Contact Us',
            'description' => $metaDescription,
            'url' => $pageUrl,
            'mainEntity' => [
                '@type' => 'NGO',
                'name' => config('app.name'),
                'url' => url('/'),
                'address' => $orgProfile?->addressLine() ? array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $orgProfile->address_line,
                    'addressLocality' => $orgProfile->city,
                    'addressRegion' => $orgProfile->state,
                    'postalCode' => $orgProfile->pin_code,
                    'addressCountry' => 'IN',
                ]) : null,
                'contactPoint' => array_values(array_filter([
                    $orgProfile?->phone_primary ? [
                        '@type' => 'ContactPoint',
                        'telephone' => $orgProfile->phone_primary,
                        'contactType' => 'customer support',
                        'areaServed' => 'IN',
                    ] : null,
                    $orgProfile?->email_primary ? [
                        '@type' => 'ContactPoint',
                        'email' => $orgProfile->email_primary,
                        'contactType' => 'customer support',
                    ] : null,
                ])),
                'sameAs' => array_values($orgProfile?->socialLinks() ?? []),
            ],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Contact Us', 'item' => $pageUrl],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    {{-- Hero --}}
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Contact Us'],
        ]" class="mb-6" />

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">We'd Love to Hear From You</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">Contact Us</h1>
            <p class="mt-4 text-base text-text-600 dark:text-night-text-muted">
                Questions about donations, volunteering, partnerships, or our programmes —
                reach out through any channel below and our team will respond within 2–3 working days.
            </p>

            @if ($orgProfile?->whatsappLink())
                <div class="mt-6">
                    <a href="{{ $orgProfile->whatsappLink() }}" target="_blank" rel="noopener"
                       class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-[#25D366] px-5 text-base font-semibold text-white shadow-sm transition hover:brightness-95">
                        <x-ui.icon name="whatsapp" class="h-5 w-5" /> Chat on WhatsApp
                    </a>
                </div>
            @endif
        </div>
    </x-ui.section>

    {{-- Contact information --}}
    <x-ui.section background="base" spacing="sm">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.card>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                    <x-ui.icon name="map-pin" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 font-display text-base font-semibold text-text-900 dark:text-night-text">Office Address</h2>
                <p class="mt-2 text-sm leading-relaxed text-text-600 dark:text-night-text-muted">
                    {{ $orgProfile?->addressLine() ?? 'Address will be published soon.' }}
                </p>
            </x-ui.card>

            <x-ui.card>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                    <x-ui.icon name="phone" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 font-display text-base font-semibold text-text-900 dark:text-night-text">Phone</h2>
                <div class="mt-2 space-y-1 text-sm text-text-600 dark:text-night-text-muted">
                    @if ($orgProfile?->phone_primary)
                        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgProfile->phone_primary) }}" class="hover:text-primary-700">{{ $orgProfile->phone_primary }}</a></p>
                    @endif
                    @if ($orgProfile?->phone_secondary)
                        <p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgProfile->phone_secondary) }}" class="hover:text-primary-700">{{ $orgProfile->phone_secondary }}</a></p>
                    @endif
                    @unless ($orgProfile?->phone_primary || $orgProfile?->phone_secondary)
                        <p>Phone numbers will be published soon.</p>
                    @endunless
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                    <x-ui.icon name="envelope" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 font-display text-base font-semibold text-text-900 dark:text-night-text">Email</h2>
                <div class="mt-2 space-y-1 text-sm text-text-600 dark:text-night-text-muted">
                    @if ($orgProfile?->email_primary)
                        <p><a href="mailto:{{ $orgProfile->email_primary }}" class="break-all hover:text-primary-700">{{ $orgProfile->email_primary }}</a></p>
                    @endif
                    @if ($orgProfile?->email_secondary)
                        <p><a href="mailto:{{ $orgProfile->email_secondary }}" class="break-all hover:text-primary-700">{{ $orgProfile->email_secondary }}</a></p>
                    @endif
                    @unless ($orgProfile?->email_primary || $orgProfile?->email_secondary)
                        <p>Email addresses will be published soon.</p>
                    @endunless
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                    <x-ui.icon name="clock" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 font-display text-base font-semibold text-text-900 dark:text-night-text">Office Hours</h2>
                <p class="mt-2 text-sm leading-relaxed text-text-600 dark:text-night-text-muted">
                    {{ $orgProfile?->office_hours ?? 'Mon–Sat, 10:00 AM – 6:00 PM' }}
                </p>
                @if ($orgProfile?->emergency_phone)
                    <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">
                        <span class="font-semibold text-error-600">Emergency helpline:</span>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $orgProfile->emergency_phone) }}" class="hover:text-primary-700">{{ $orgProfile->emergency_phone }}</a>
                    </p>
                @endif
            </x-ui.card>
        </div>

        @if ($orgProfile && $orgProfile->socialLinks() !== [])
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <span class="text-sm font-medium text-text-600 dark:text-night-text-muted">Follow our work:</span>
                @foreach ($orgProfile->socialLinks() as $platform => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                       aria-label="{{ ucfirst($platform) }}"
                       class="flex h-10 w-10 items-center justify-center rounded-full border border-border-subtle text-text-600 transition hover:border-primary-700 hover:text-primary-700 dark:border-night-border dark:text-night-text-muted">
                        <x-ui.icon :name="$platform === 'twitter' ? 'x-twitter' : $platform" class="h-5 w-5" />
                    </a>
                @endforeach
            </div>
        @endif
    </x-ui.section>

    {{-- Form + map --}}
    <x-ui.section background="white" spacing="lg" id="contact-form">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-2">
            <div>
                <h2 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">Send Us a Message</h2>
                <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">Fields marked * are required. We only use your details to respond to your enquiry.</p>

                @if (session('enquiry_status'))
                    <div class="mt-4">
                        <x-ui.alert variant="success">{{ session('enquiry_status') }}</x-ui.alert>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-4">
                        <x-ui.alert variant="error">Please correct the highlighted fields below and submit again.</x-ui.alert>
                    </div>
                @endif

                @php
                    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 placeholder:text-text-400 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text dark:placeholder:text-night-text-muted';
                @endphp

                <form
                    method="POST"
                    action="{{ route('contact.store') }}"
                    enctype="multipart/form-data"
                    class="mt-6 space-y-5"
                    x-data="{ submitting: false }"
                    @submit="submitting = true"
                >
                    @csrf

                    {{-- Honeypot: hidden from humans; bots that fill it are silently discarded. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-ui.input label="Full Name *" name="name" value="{{ old('name') }}" required autocomplete="name" :error="$errors->first('name')" />
                        <x-ui.input label="Email *" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" :error="$errors->first('email')" />
                        <x-ui.input label="Phone Number *" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" :error="$errors->first('phone')" />
                        <x-ui.select label="Category *" name="category" :options="['' => 'What is this about?'] + $categories" :selected="old('category')" required :error="$errors->first('category')" />
                    </div>

                    <x-ui.input label="Subject *" name="subject" value="{{ old('subject') }}" required placeholder="A short summary of your enquiry" :error="$errors->first('subject')" />

                    <div>
                        <label for="message" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Message *</label>
                        <textarea id="message" name="message" rows="5" required placeholder="How can we help you?" class="{{ $textareaClasses }} @error('message') !border-error-600 @enderror">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="attachment" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Attachment (optional)</label>
                        <input id="attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx"
                            class="block w-full text-sm text-text-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100/70 dark:text-night-text-muted">
                        @error('attachment')
                            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Image, PDF or Word document, up to 4 MB.</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-3 text-sm text-text-600 dark:text-night-text-muted">
                            <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                                class="mt-0.5 rounded border-border-subtle text-primary-700 focus:ring-primary-700/35">
                            <span>
                                I consent to {{ config('app.name') }} storing my details and
                                contacting me about this enquiry. *
                            </span>
                        </label>
                        @error('consent')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                    </div>

                    @if ($recaptchaSiteKey)
                        <div>
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            @error('g-recaptcha-response')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <x-ui.button type="submit" variant="accent" class="w-full sm:w-auto" x-bind:disabled="submitting">
                        <span x-show="!submitting">Send Message</span>
                        <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                            <x-ui.loading size="sm" label="Sending" /> Sending…
                        </span>
                    </x-ui.button>
                </form>
            </div>

            <div>
                <h2 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">Find Us</h2>
                @if ($orgProfile?->map_embed_url && Str::contains($orgProfile->map_embed_url, '/maps/embed'))
                    <div class="mt-6 aspect-[4/3] w-full overflow-hidden rounded-xl border border-border-subtle dark:border-night-border">
                        <iframe
                            src="{{ $orgProfile->map_embed_url }}"
                            class="h-full w-full border-0"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Map: {{ config('app.name') }} office location"
                        ></iframe>
                    </div>
                @else
                    <div class="mt-6 flex aspect-[4/3] w-full items-center justify-center rounded-xl border border-dashed border-border-subtle bg-surface-muted text-sm text-text-400 dark:border-night-border dark:bg-night-surface-alt dark:text-night-text-muted">
                        Map will be available soon.
                    </div>
                @endif

                <div class="mt-6 rounded-lg border border-border-subtle bg-surface-muted p-5 text-sm leading-relaxed text-text-600 dark:border-night-border dark:bg-night-surface-alt dark:text-night-text-muted">
                    <p class="font-semibold text-text-900 dark:text-night-text">Prefer other ways to get involved?</p>
                    <p class="mt-2">
                        Ready to give your time? <a href="{{ route('volunteer.create') }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Apply as a volunteer</a>.
                        Want to support a cause? <a href="{{ route('donations.campaigns.index') }}" class="font-medium text-primary-700 hover:underline dark:text-night-text">Browse our campaigns</a>.
                    </p>
                </div>
            </div>
        </div>
    </x-ui.section>
@endsection

@if ($recaptchaSiteKey)
    @push('scripts')
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endpush
@endif
