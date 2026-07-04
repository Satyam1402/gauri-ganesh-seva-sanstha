<?php

namespace App\Services;

use App\Models\OrgProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class OrgProfileService
{
    public const CACHE_KEY = 'org-profile';

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(OrgProfile $profile, array $data): OrgProfile
    {
        $profile->update([
            'legal_name' => $data['legal_name'] ?? null,
            'short_name' => $data['short_name'] ?? null,
            'registration_no' => $data['registration_no'] ?? null,
            'registration_date' => $data['registration_date'] ?? null,
            'pan_no' => $data['pan_no'] ?? null,
            'trust_deed_no' => $data['trust_deed_no'] ?? null,
            'section_80g_no' => $data['section_80g_no'] ?? null,
            'section_12a_no' => $data['section_12a_no'] ?? null,
            'established_year' => $data['established_year'] ?? null,
        ]);

        $this->addFiles($profile, $data['new_certificates'] ?? [], 'certificates');
        $this->removeMedia($profile, $data['remove_certificate_ids'] ?? [], 'certificates');

        $this->addFiles($profile, $data['new_documents'] ?? [], 'legal_documents');
        $this->removeMedia($profile, $data['remove_document_ids'] ?? [], 'legal_documents');

        Cache::forget(self::CACHE_KEY);

        return $profile->refresh();
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function addFiles(OrgProfile $profile, array $files, string $collection): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $profile->addMedia($file)->toMediaCollection($collection);
            }
        }
    }

    /**
     * @param  list<int>  $mediaIds
     */
    private function removeMedia(OrgProfile $profile, array $mediaIds, string $collection): void
    {
        foreach ($mediaIds as $mediaId) {
            $profile->getMedia($collection)->firstWhere('id', $mediaId)?->delete();
        }
    }
}
