<?php

namespace App\Services;

use App\Models\OrgProfile;
use App\Models\Setting;
use App\Support\SettingsRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single read/write path for site settings.
 *
 * Reads come from one cached map ("settings.all") built from the settings
 * table + registry defaults + the org_profiles singleton, so a page render
 * costs zero settings queries. Every write busts that one key, so changes
 * are visible on the very next request.
 *
 * Bound as a singleton (SettingsServiceProvider) so the map is resolved at
 * most once per request even when called hundreds of times from Blade.
 */
class SettingsService
{
    public const CACHE_KEY = 'settings.all';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $resolved = null;

    /**
     * Read one setting by "group.key". Media settings return an array with
     * url / webp keys (see mediaUrl() for the convenient form).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Arr::get($this->all(), $key);

        return $value === null || $value === '' ? $default : $value;
    }

    /**
     * Whether a setting has a non-empty value.
     */
    public function has(string $key): bool
    {
        $value = Arr::get($this->all(), $key);

        return ! ($value === null || $value === '' || $value === []);
    }

    /**
     * URL of a media setting, optionally a conversion ("webp"), or null.
     */
    public function mediaUrl(string $key, ?string $conversion = null): ?string
    {
        $media = $this->get($key);

        if (! is_array($media)) {
            return null;
        }

        if ($conversion !== null && ! empty($media[$conversion])) {
            return $media[$conversion];
        }

        return $media['url'] ?? null;
    }

    /**
     * All settings as a nested [group => [key => value]] array. Cached
     * forever; busted on every write.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        try {
            return $this->resolved = Cache::rememberForever(self::CACHE_KEY, fn () => $this->build());
        } catch (QueryException) {
            // Before the tables exist (fresh install, mid-migration) fall
            // back to registry defaults so early boot never explodes. Not
            // memoised, so the first call after migration reads the DB.
            return $this->defaultsMap();
        }
    }

    /**
     * Values for one admin tab, keyed by field, for the edit form.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        return $this->all()[$group] ?? [];
    }

    /**
     * Persist a validated group of values (and uploaded files).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateGroup(string $group, array $data): void
    {
        $fields = SettingsRegistry::fields($group);

        if (SettingsRegistry::source($group) === SettingsRegistry::SOURCE_ORG_PROFILE) {
            $this->updateOrgProfile($fields, $data);
        } else {
            DB::transaction(function () use ($group, $fields, $data) {
                foreach ($fields as $key => $field) {
                    SettingsRegistry::isMedia($field)
                        ? $this->storeMedia($group, $key, $field, $data)
                        : $this->store($group, $key, $field, $data[$key] ?? null);
                }
            });
        }

        $this->forgetCache();
    }

    /**
     * Programmatic single write (used by tests and maintenance toggles).
     */
    public function set(string $dotKey, mixed $value): void
    {
        [$group, $key] = explode('.', $dotKey, 2);
        $field = SettingsRegistry::field($group, $key) ?? ['type' => 'text'];

        $this->store($group, $key, $field, $value);
        $this->forgetCache();
    }

    /**
     * Push non-secret settings into the runtime config so the framework and
     * existing modules (config('app.name') in 120+ templates and every mail
     * subject, donation defaults, offline payment details, the public
     * reCAPTCHA key) follow the database. Called at boot and again right
     * after a save so the change is visible within the same request.
     *
     * Gateway secrets, SMTP passwords and API keys are never touched.
     */
    public function applyConfigOverrides(): void
    {
        $overrides = [];

        if ($name = $this->get('general.site_name')) {
            $overrides['app.name'] = $name;
            $overrides['mail.from.name'] = $name;
        }

        if (($timezone = $this->get('general.timezone')) && in_array($timezone, timezone_identifiers_list(), true)) {
            $overrides['app.timezone'] = $timezone;
            date_default_timezone_set($timezone);
        }

        if ($locale = $this->get('general.default_language')) {
            $overrides['app.locale'] = $locale;
        }

        if ($currency = $this->get('donation.currency')) {
            $overrides['donations.currency'] = $currency;
        }

        if (($min = $this->get('donation.min_amount')) !== null) {
            $overrides['donations.min_amount'] = (float) $min;
        }

        foreach ([
            ['donation.bank_account_name', 'donations.gateways.bank_transfer.account_name'],
            ['donation.bank_account_name', 'donations.gateways.upi.payee_name'],
            ['donation.account_number', 'donations.gateways.bank_transfer.account_number'],
            ['donation.ifsc', 'donations.gateways.bank_transfer.ifsc'],
            ['donation.bank_name', 'donations.gateways.bank_transfer.bank_name'],
            ['donation.branch', 'donations.gateways.bank_transfer.branch'],
            ['donation.upi_id', 'donations.gateways.upi.vpa'],
            ['integrations.recaptcha_site_key', 'services.recaptcha.site_key'],
        ] as [$settingKey, $configKey]) {
            $value = $this->get($settingKey);

            if ($value !== null && $value !== '') {
                $overrides[$configKey] = $value;
            }
        }

        config($overrides);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(OrgProfileService::CACHE_KEY);
        $this->resolved = null;
        $this->applyConfigOverrides();
    }

    /**
     * Env-backed secrets the Integrations tab reports on — presence only,
     * never the value.
     *
     * @return array<string, bool>
     */
    public function envSecretStatus(): array
    {
        return [
            'reCAPTCHA secret key (RECAPTCHA_SECRET_KEY)' => filled(config('services.recaptcha.secret_key')),
            'Razorpay key & secret (RAZORPAY_KEY / RAZORPAY_SECRET)' => filled(config('donations.gateways.razorpay.key')) && filled(config('donations.gateways.razorpay.secret')),
            'Stripe key & secret (STRIPE_KEY / STRIPE_SECRET)' => filled(config('donations.gateways.stripe.key')) && filled(config('donations.gateways.stripe.secret')),
            'PayPal client & secret (PAYPAL_CLIENT_ID / PAYPAL_SECRET)' => filled(config('donations.gateways.paypal.client_id')) && filled(config('donations.gateways.paypal.secret')),
            'Mail password (MAIL_PASSWORD)' => filled(config('mail.mailers.smtp.password')),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function build(): array
    {
        $map = $this->defaultsMap();

        $rows = Setting::query()->with('media')->get();

        foreach ($rows as $row) {
            if (! SettingsRegistry::hasGroup($row->group)) {
                continue;
            }

            $map[$row->group][$row->key] = $row->type === Setting::TYPE_MEDIA
                ? $this->mediaPayload($row)
                : $row->castValue();
        }

        $profile = OrgProfile::query()->first();

        foreach (SettingsRegistry::groups() as $group => $definition) {
            if ($definition['source'] !== SettingsRegistry::SOURCE_ORG_PROFILE) {
                continue;
            }

            foreach (array_keys($definition['fields']) as $key) {
                $value = $profile?->getAttribute($key);
                $map[$group][$key] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
            }
        }

        return $map;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultsMap(): array
    {
        $map = [];

        foreach (SettingsRegistry::groups() as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                $map[$group][$key] = $field['default'] ?? null;
            }
        }

        return $map;
    }

    /**
     * @return array{url: string, webp: ?string, name: string}|null
     */
    private function mediaPayload(Setting $row): ?array
    {
        $media = $row->getFirstMedia('image') ?? $row->getFirstMedia('raw');

        if ($media === null) {
            return null;
        }

        return [
            'url' => $media->getUrl(),
            'webp' => $media->hasGeneratedConversion('webp') ? $media->getUrl('webp') : null,
            'name' => $media->file_name,
        ];
    }

    private function store(string $group, string $key, array $field, mixed $value): void
    {
        $type = SettingsRegistry::storageType($field['type']);

        $stored = match ($type) {
            Setting::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            Setting::TYPE_JSON => $value === null ? null : json_encode($value),
            default => $value === null || $value === '' ? null : (string) $value,
        };

        Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'type' => $type],
        );
    }

    /**
     * Upload replaces the previous file (single-file collections); the
     * "remove_{key}" flag clears it. Anything else leaves it untouched.
     */
    private function storeMedia(string $group, string $key, array $field, array $data): void
    {
        $file = $data[$key] ?? null;
        $remove = ! empty($data['remove_'.$key]);

        if (! $file instanceof UploadedFile && ! $remove) {
            return;
        }

        $row = Setting::query()->firstOrCreate(
            ['group' => $group, 'key' => $key],
            ['type' => Setting::TYPE_MEDIA],
        );

        $row->clearMediaCollection('image');
        $row->clearMediaCollection('raw');

        if ($file instanceof UploadedFile) {
            // .ico must not be transcoded; everything else gets a WebP copy.
            $collection = Str::endsWith(strtolower($file->getClientOriginalName()), '.ico') ? 'raw' : 'image';
            $row->addMedia($file)->toMediaCollection($collection);
            $row->update(['value' => 'media', 'type' => Setting::TYPE_MEDIA]);
        } else {
            $row->update(['value' => null, 'type' => Setting::TYPE_MEDIA]);
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $data
     */
    private function updateOrgProfile(array $fields, array $data): void
    {
        $profile = OrgProfile::query()->firstOrCreate([]);

        $attributes = [];

        foreach (array_keys($fields) as $key) {
            $attributes[$key] = ($data[$key] ?? null) === '' ? null : ($data[$key] ?? null);
        }

        $profile->update($attributes);
    }
}
