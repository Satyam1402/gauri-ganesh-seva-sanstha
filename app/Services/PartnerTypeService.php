<?php

namespace App\Services;

use App\Interfaces\PartnerTypeRepositoryInterface;
use App\Models\PartnerType;
use Illuminate\Validation\ValidationException;

class PartnerTypeService
{
    public function __construct(
        private PartnerTypeRepositoryInterface $types,
        private PartnerService $partnerService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createType(array $data): PartnerType
    {
        $type = $this->types->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->forgetCache();

        return $type;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateType(PartnerType $type, array $data): PartnerType
    {
        $this->types->update($type, [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $type->slug,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $this->forgetCache();

        return $type->refresh();
    }

    /**
     * Hide or re-show a type. Hiding also hides its partners publicly.
     */
    public function toggleActive(PartnerType $type): PartnerType
    {
        $this->types->update($type, ['is_active' => ! $type->is_active]);
        $this->forgetCache();

        return $type;
    }

    public function deleteType(PartnerType $type): bool
    {
        if ($type->partners()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'type' => 'This type has partners assigned to it and cannot be deleted. Deactivate it instead, or move its partners first.',
            ]);
        }

        $deleted = $this->types->delete($type);
        $this->forgetCache();

        return $deleted;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->types->reorder($orderedIds);
        $this->forgetCache();
    }

    /**
     * Type changes affect grouping, tab counts and visibility, so the
     * partner caches are busted too (PartnerService clears the type cache).
     */
    private function forgetCache(): void
    {
        $this->partnerService->forgetCache();
    }
}
