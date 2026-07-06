<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA v2 verification. The rule is a no-op until a secret key
 * is configured (RECAPTCHA_SECRET_KEY), so forms keep working on installs
 * that have not set up reCAPTCHA yet.
 */
class Recaptcha implements ValidationRule
{
    /**
     * Run even when the field is absent — bots simply omit the token, and
     * skipping validation on missing input would defeat the check entirely.
     */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret_key');

        if (! $secret) {
            return;
        }

        if (blank($value)) {
            $fail('Please confirm you are not a robot.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ]);

            if (! $response->json('success')) {
                $fail('reCAPTCHA verification failed. Please try again.');
            }
        } catch (\Throwable $e) {
            // Fail open on Google outages: rate limiting and the honeypot
            // still protect the form, and legitimate visitors are not locked out.
            Log::warning('reCAPTCHA verification unreachable', ['error' => $e->getMessage()]);
        }
    }
}
