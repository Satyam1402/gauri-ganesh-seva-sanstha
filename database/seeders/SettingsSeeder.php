<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use App\Support\SettingsRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SettingsSeeder extends Seeder
{
    /**
     * Writes registry defaults for settings that do not exist yet. Existing
     * values are never overwritten, so re-seeding is safe. Organisation,
     * contact and social values live on org_profiles (OrgProfileSeeder).
     */
    public function run(): void
    {
        foreach (SettingsRegistry::groups() as $group => $definition) {
            if ($definition['source'] !== SettingsRegistry::SOURCE_SETTINGS) {
                continue;
            }

            foreach ($definition['fields'] as $key => $field) {
                if (SettingsRegistry::isMedia($field)) {
                    continue;
                }

                $default = $field['default'] ?? null;

                if ($default === null) {
                    continue;
                }

                $type = SettingsRegistry::storageType($field['type']);

                Setting::query()->firstOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['type' => $type, 'value' => $type === Setting::TYPE_BOOLEAN ? ($default ? '1' : '0') : (string) $default],
                );
            }
        }

        Cache::forget(SettingsService::CACHE_KEY);
    }
}
