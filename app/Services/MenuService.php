<?php

namespace App\Services;

use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use App\Interfaces\MenuItemRepositoryInterface;
use App\Models\MenuItem;
use App\Repositories\MenuItemRepository;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    public function __construct(private MenuItemRepositoryInterface $items) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): MenuItem
    {
        $location = MenuLocation::from($data['location']);

        /** @var MenuItem $item */
        $item = $this->items->create($this->attributes($data, $location));

        $this->forgetCache($location);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(MenuItem $item, array $data): MenuItem
    {
        // Location is fixed after creation; only the link/label change.
        $this->items->update($item, $this->attributes($data, $item->location, $item));

        $this->forgetCache($item->location);

        return $item->refresh();
    }

    public function toggleActive(MenuItem $item): MenuItem
    {
        $this->items->update($item, ['is_active' => ! $item->is_active]);
        $this->forgetCache($item->location);

        return $item;
    }

    public function deleteItem(MenuItem $item): bool
    {
        $location = $item->location;
        // Children cascade at the database level.
        $deleted = $this->items->delete($item);
        $this->forgetCache($location);

        return $deleted;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(MenuLocation $location, array $orderedIds): void
    {
        $this->items->reorder($orderedIds);
        $this->forgetCache($location);
    }

    public function forgetCache(?MenuLocation $location = null): void
    {
        foreach ($location ? [$location] : MenuLocation::cases() as $case) {
            Cache::forget(MenuItemRepository::cacheKey($case));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, MenuLocation $location, ?MenuItem $existing = null): array
    {
        $type = MenuLinkType::from($data['link_type']);

        // Children only exist in the header, and only one level deep: a
        // parent must itself be a top-level item of the same location.
        $parentId = null;
        if ($location->supportsChildren() && ! empty($data['parent_id'])) {
            $parent = MenuItem::query()->whereKey((int) $data['parent_id'])->first();
            if ($parent && $parent->location === $location && $parent->parent_id === null && $parent->id !== $existing?->id) {
                $parentId = $parent->id;
            }
        }

        return [
            'location' => $location->value,
            'parent_id' => $parentId,
            'label' => $data['label'],
            'link_type' => $type->value,
            'route_name' => $type === MenuLinkType::Route ? $data['route_name'] : null,
            'url' => $type === MenuLinkType::Route ? null : $data['url'],
            'open_in_new_tab' => $type === MenuLinkType::External && (bool) ($data['open_in_new_tab'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'order_column' => $existing?->order_column ?? (int) (MenuItem::query()->forLocation($location)->max('order_column') + 1),
        ];
    }
}
