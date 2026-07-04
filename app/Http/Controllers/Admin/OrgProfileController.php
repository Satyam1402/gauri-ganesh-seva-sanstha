<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrgProfileRequest;
use App\Models\OrgProfile;
use App\Services\OrgProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrgProfileController extends Controller
{
    public function __construct(private OrgProfileService $orgProfileService) {}

    public function edit(): View
    {
        $profile = OrgProfile::query()->firstOrCreate([]);

        $this->authorize('update', $profile);

        return view('admin.org-profile.edit', [
            'profile' => $profile->load('media'),
        ]);
    }

    public function update(UpdateOrgProfileRequest $request): RedirectResponse
    {
        $profile = OrgProfile::query()->firstOrCreate([]);

        $this->authorize('update', $profile);

        $this->orgProfileService->updateProfile($profile, $request->validated());

        return redirect()->route('admin.org-profile.edit')
            ->with('status', 'Organization profile updated successfully.');
    }
}
