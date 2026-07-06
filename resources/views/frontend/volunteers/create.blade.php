@extends('layouts.app')

@php
    $seo = $page?->seo;
    $pageUrl = route('volunteer.create');
    // SEO fallback chains deliberately end in string literals — an inline
    // @section with a null value leaks an unclosed output buffer.
    $metaDescription = $seo?->meta_description
        ?? 'Become a volunteer with '.config('app.name').' — join our food distribution drives, education programmes, and medical camps. Apply online today.';
@endphp

@section('title', $seo?->meta_title ?? 'Become a Volunteer — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $pageUrl)
@section('og_title', $seo?->og_title ?? 'Become a Volunteer')
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage)
    @section('og_image', $seo->ogImage->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'WebPage',
            'name' => $seo?->meta_title ?? 'Become a Volunteer',
            'description' => $metaDescription,
            'url' => $pageUrl,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'Become a Volunteer', 'item' => $pageUrl],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    {{-- Hero --}}
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Become a Volunteer'],
        ]" class="mb-6" />

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">Join Our Mission</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">Become a Volunteer</h1>
            <p class="mt-4 text-base text-text-600 dark:text-night-text-muted">
                Every meal served, every child taught, and every patient cared for begins with a
                volunteer who chose to show up. Lend your time and skills — and help us restore
                dignity in the communities we serve.
            </p>
            <div class="mt-6">
                <x-ui.button href="#application-form" variant="accent">Apply Now</x-ui.button>
            </div>
        </div>
    </x-ui.section>

    {{-- Benefits --}}
    <x-ui.section background="base" spacing="lg">
        <p class="text-center text-sm font-semibold uppercase tracking-wide text-accent-500">Why Volunteer With Us</p>
        <x-ui.section-heading
            heading="What You Gain by Giving"
            subheading="Volunteering is seva — and it gives back in ways money never can."
            align="center"
            class="mt-2"
        />

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'heart', 'title' => 'Meaningful Impact', 'text' => 'Serve food, teach children, and support medical camps — see the direct difference your hours make in real lives.'],
                ['icon' => 'users', 'title' => 'Community & Belonging', 'text' => 'Work alongside like-minded volunteers and build friendships rooted in service and shared purpose.'],
                ['icon' => 'academic-cap', 'title' => 'New Skills & Experience', 'text' => 'Gain hands-on experience in event management, teaching, outreach, and social work — great for any résumé.'],
                ['icon' => 'document-check', 'title' => 'Certificates & Recognition', 'text' => 'Receive volunteering certificates and letters of appreciation for your contribution to our programmes.'],
                ['icon' => 'calendar', 'title' => 'Flexible Commitment', 'text' => 'Weekdays, weekends, or on-call — volunteer in a way that fits around your studies, job, and family.'],
                ['icon' => 'sparkles', 'title' => 'Personal Growth', 'text' => 'Volunteers consistently tell us the same thing: they came to give, and left having received far more.'],
            ] as $benefit)
                <x-ui.card>
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                        <x-ui.icon :name="$benefit['icon']" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-4 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $benefit['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $benefit['text'] }}</p>
                </x-ui.card>
            @endforeach
        </div>
    </x-ui.section>

    {{-- Process timeline --}}
    <x-ui.section background="white" spacing="lg">
        <p class="text-center text-sm font-semibold uppercase tracking-wide text-accent-500">How It Works</p>
        <x-ui.section-heading
            heading="Your Journey to Volunteering"
            subheading="From application to your first seva activity in four simple steps."
            align="center"
            class="mt-2"
        />

        <ol class="mx-auto mt-10 grid max-w-5xl grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['title' => 'Apply Online', 'text' => 'Fill in the application form below with your details, skills, and areas of interest.'],
                ['title' => 'Application Review', 'text' => 'Our volunteer coordinator reviews your application, usually within 5–7 working days.'],
                ['title' => 'Introduction Call', 'text' => 'We get in touch on your preferred channel for a short conversation and orientation.'],
                ['title' => 'Start Volunteering', 'text' => 'You are matched to activities that fit your interests and availability. Welcome aboard!'],
            ] as $step)
                <li class="relative text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-700 font-display text-lg font-semibold text-white" aria-hidden="true">
                        {{ $loop->iteration }}
                    </div>
                    @unless ($loop->last)
                        <div class="absolute left-[calc(50%+2rem)] top-6 hidden h-px w-[calc(100%-4rem)] bg-border-subtle lg:block dark:bg-night-border" aria-hidden="true"></div>
                    @endunless
                    <h3 class="mt-4 font-display text-base font-semibold text-text-900 dark:text-night-text">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-text-600 dark:text-night-text-muted">{{ $step['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </x-ui.section>

    {{-- Application form --}}
    <x-ui.section background="base" spacing="lg" id="application-form">
        <p class="text-center text-sm font-semibold uppercase tracking-wide text-accent-500">Application Form</p>
        <x-ui.section-heading
            heading="Tell Us About Yourself"
            subheading="Fields marked * are required. Your details stay private and are used only to coordinate your volunteering."
            align="center"
            class="mt-2"
        />

        @if ($errors->any())
            <div class="mx-auto mt-8 max-w-3xl">
                <x-ui.alert variant="error">
                    Please correct the highlighted fields below and submit again.
                </x-ui.alert>
            </div>
        @endif

        @php
            $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 placeholder:text-text-400 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text dark:placeholder:text-night-text-muted';
            $legendClasses = 'font-display text-lg font-semibold text-text-900 dark:text-night-text';
        @endphp

        <form
            method="POST"
            action="{{ route('volunteer.store') }}"
            enctype="multipart/form-data"
            class="mx-auto mt-10 max-w-3xl space-y-10 rounded-xl border border-border-subtle bg-surface-white p-6 sm:p-10 dark:border-night-border dark:bg-night-surface"
            x-data="{ submitting: false }"
            @submit="submitting = true"
        >
            @csrf

            {{-- Personal details --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Personal Details</legend>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.input label="First Name *" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" :error="$errors->first('first_name')" />
                    <x-ui.input label="Last Name *" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" :error="$errors->first('last_name')" />
                    <x-ui.select label="Gender *" name="gender" :options="['' => 'Select gender…'] + $genders" :selected="old('gender')" required :error="$errors->first('gender')" />
                    <x-ui.input label="Date of Birth *" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required max="{{ now()->subYears(16)->toDateString() }}" helper="Volunteers must be at least 16 years old." :error="$errors->first('date_of_birth')" />
                </div>

                <div>
                    <label for="profile_photo" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Profile Photo (optional)</label>
                    <input id="profile_photo" name="profile_photo" type="file" accept=".jpg,.jpeg,.png,.webp"
                        class="block w-full text-sm text-text-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100/70 dark:text-night-text-muted">
                    @error('profile_photo')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @else
                        <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">JPG, PNG or WebP, up to 2 MB.</p>
                    @enderror
                </div>
            </fieldset>

            {{-- Contact --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Contact Information</legend>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.input label="Email *" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" :error="$errors->first('email')" />
                    <x-ui.select label="Preferred Communication *" name="preferred_communication_method" :options="['' => 'How should we contact you?'] + $communicationMethods" :selected="old('preferred_communication_method')" required :error="$errors->first('preferred_communication_method')" />
                    <x-ui.input label="Phone Number *" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" :error="$errors->first('phone')" />
                    <x-ui.input label="Alternate Phone (optional)" name="alternate_phone" type="tel" value="{{ old('alternate_phone') }}" :error="$errors->first('alternate_phone')" />
                </div>
            </fieldset>

            {{-- Address --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Address</legend>

                <x-ui.input label="Street Address *" name="address" value="{{ old('address') }}" required autocomplete="street-address" :error="$errors->first('address')" />

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.input label="City *" name="city" value="{{ old('city') }}" required autocomplete="address-level2" :error="$errors->first('city')" />
                    <x-ui.input label="State *" name="state" value="{{ old('state') }}" required autocomplete="address-level1" :error="$errors->first('state')" />
                    <x-ui.input label="Country *" name="country" value="{{ old('country', 'India') }}" required autocomplete="country-name" :error="$errors->first('country')" />
                    <x-ui.input label="PIN Code *" name="pin_code" value="{{ old('pin_code') }}" required autocomplete="postal-code" inputmode="numeric" :error="$errors->first('pin_code')" />
                </div>
            </fieldset>

            {{-- Professional background --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Professional Background</legend>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.input label="Occupation *" name="occupation" value="{{ old('occupation') }}" required placeholder="e.g. Student, Teacher, Engineer" :error="$errors->first('occupation')" />
                    <x-ui.input label="Organization (optional)" name="organization" value="{{ old('organization') }}" placeholder="Employer, college, or institution" :error="$errors->first('organization')" />
                </div>

                <div>
                    <label for="skills" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Skills *</label>
                    <textarea id="skills" name="skills" rows="3" required placeholder="e.g. Teaching, first aid, photography, driving, cooking…" class="{{ $textareaClasses }} @error('skills') !border-error-600 @enderror">{{ old('skills') }}</textarea>
                    @error('skills')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="experience" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Previous Volunteering Experience (optional)</label>
                    <textarea id="experience" name="experience" rows="3" placeholder="Tell us about any volunteering or social work you have done before." class="{{ $textareaClasses }} @error('experience') !border-error-600 @enderror">{{ old('experience') }}</textarea>
                    @error('experience')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            {{-- Volunteering preferences --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Volunteering Preferences</legend>

                <div>
                    <p class="mb-2 block text-sm font-medium text-text-900 dark:text-night-text">Areas of Interest * <span class="font-normal text-text-400 dark:text-night-text-muted">(choose at least one)</span></p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($areasOfInterest as $key => $label)
                            <label class="flex items-center gap-3 rounded-md border border-border-subtle px-4 py-2.5 text-sm text-text-900 has-[:checked]:border-primary-700 has-[:checked]:bg-primary-100/50 dark:border-night-border dark:text-night-text dark:has-[:checked]:bg-night-surface-alt">
                                <input type="checkbox" name="areas_of_interest[]" value="{{ $key }}"
                                    @checked(in_array($key, old('areas_of_interest', []), true))
                                    class="rounded border-border-subtle text-primary-700 focus:ring-primary-700/35">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('areas_of_interest')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                    @error('areas_of_interest.*')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>

                <div class="max-w-sm">
                    <x-ui.select label="Availability *" name="availability" :options="['' => 'When can you volunteer?'] + $availabilities" :selected="old('availability')" required :error="$errors->first('availability')" />
                </div>
            </fieldset>

            {{-- Emergency & wellbeing --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Emergency Contact & Wellbeing</legend>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.input label="Emergency Contact Name *" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" required :error="$errors->first('emergency_contact_name')" />
                    <x-ui.input label="Emergency Contact Phone *" name="emergency_contact_phone" type="tel" value="{{ old('emergency_contact_phone') }}" required :error="$errors->first('emergency_contact_phone')" />
                </div>

                <div>
                    <label for="medical_information" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Medical Information (optional)</label>
                    <textarea id="medical_information" name="medical_information" rows="2" placeholder="Allergies, conditions, or anything we should know to keep you safe during field activities." class="{{ $textareaClasses }} @error('medical_information') !border-error-600 @enderror">{{ old('medical_information') }}</textarea>
                    @error('medical_information')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            {{-- Documents --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Documents <span class="text-sm font-normal text-text-400 dark:text-night-text-muted">(optional — stored securely, visible only to our team)</span></legend>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="identity_proof" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Identity Proof</label>
                        <input id="identity_proof" name="identity_proof" type="file" accept=".jpg,.jpeg,.png,.pdf"
                            class="block w-full text-sm text-text-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100/70 dark:text-night-text-muted">
                        @error('identity_proof')
                            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Aadhaar, PAN, etc. — JPG, PNG or PDF, up to 4 MB.</p>
                        @enderror
                    </div>

                    <div>
                        <label for="resume" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Resume / CV</label>
                        <input id="resume" name="resume" type="file" accept=".pdf,.doc,.docx"
                            class="block w-full text-sm text-text-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-primary-700 hover:file:bg-primary-100/70 dark:text-night-text-muted">
                        @error('resume')
                            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                        @else
                            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">PDF, DOC or DOCX, up to 4 MB.</p>
                        @enderror
                    </div>
                </div>
            </fieldset>

            {{-- Message + consent --}}
            <fieldset class="space-y-5">
                <legend class="{{ $legendClasses }}">Almost Done</legend>

                <div>
                    <label for="message" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Message (optional)</label>
                    <textarea id="message" name="message" rows="3" placeholder="Why do you want to volunteer with us?" class="{{ $textareaClasses }} @error('message') !border-error-600 @enderror">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm text-text-600 dark:text-night-text-muted">
                        <input type="checkbox" name="consent" value="1" required @checked(old('consent'))
                            class="mt-0.5 rounded border-border-subtle text-primary-700 focus:ring-primary-700/35">
                        <span>
                            I confirm that the information provided is accurate, and I consent to
                            {{ config('app.name') }} storing my details and contacting me about
                            volunteering opportunities. *
                        </span>
                    </label>
                    @error('consent')<p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>@enderror
                </div>

                <x-ui.button type="submit" variant="accent" size="lg" class="w-full sm:w-auto" x-bind:disabled="submitting">
                    <span x-show="!submitting">Submit Application</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                        <x-ui.loading size="sm" label="Submitting" /> Submitting…
                    </span>
                </x-ui.button>
            </fieldset>
        </form>
    </x-ui.section>
@endsection
