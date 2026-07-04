<?php

namespace App\Services;

use App\Interfaces\AboutSectionRepositoryInterface;
use App\Models\AboutSection;
use App\Models\AboutSectionItem;
use App\Repositories\AboutSectionRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class AboutSectionService
{
    public function __construct(private AboutSectionRepositoryInterface $sections) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(AboutSection $section, array $data): AboutSection
    {
        $this->sections->update($section, [
            'heading' => $data['heading'] ?? null,
            'subheading' => $data['subheading'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if ($data['image'] ?? null instanceof UploadedFile) {
            $section->addMedia($data['image'])->toMediaCollection('image');
        } elseif (! empty($data['remove_image'])) {
            $section->clearMediaCollection('image');
        }

        if ($data['background_image'] ?? null instanceof UploadedFile) {
            $section->addMedia($data['background_image'])->toMediaCollection('background_image');
        } elseif (! empty($data['remove_background_image'])) {
            $section->clearMediaCollection('background_image');
        }

        $this->syncButtons($section, $data['buttons'] ?? []);
        $this->syncItems($section, $data['items'] ?? []);

        $this->forgetCache();

        return $section->refresh();
    }

    public function toggle(AboutSection $section): AboutSection
    {
        $this->sections->update($section, ['is_active' => ! $section->is_active]);
        $this->forgetCache();

        return $section;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->sections->reorder($orderedIds);
        $this->forgetCache();
    }

    /**
     * @param  list<array<string, mixed>>  $buttons
     */
    private function syncButtons(AboutSection $section, array $buttons): void
    {
        $section->buttons()->delete();

        foreach (array_values($buttons) as $order => $button) {
            if (empty($button['label']) || empty($button['url'])) {
                continue;
            }

            $section->buttons()->create([
                'label' => $button['label'],
                'url' => $button['url'],
                'variant' => $button['variant'] ?? 'primary',
                'order_column' => $order,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function syncItems(AboutSection $section, array $items): void
    {
        $keptIds = [];

        foreach (array_values($items) as $order => $itemData) {
            if (empty($itemData['title'])) {
                continue;
            }

            $item = ! empty($itemData['id'])
                ? $section->items()->find($itemData['id'])
                : null;
            $item ??= new AboutSectionItem(['about_section_id' => $section->id]);

            $item->fill([
                'title' => $itemData['title'],
                'subtitle' => $itemData['subtitle'] ?? null,
                'description' => $itemData['description'] ?? null,
                'icon' => $itemData['icon'] ?? null,
                'link_url' => $itemData['link_url'] ?? null,
                'is_active' => ! empty($itemData['is_active']),
                'order_column' => $order,
            ]);
            $item->save();

            if (($itemData['image'] ?? null) instanceof UploadedFile) {
                $item->addMedia($itemData['image'])->toMediaCollection('image');
            }

            $keptIds[] = $item->id;
        }

        $section->items()->whereNotIn('id', $keptIds)->delete();
    }

    private function forgetCache(): void
    {
        Cache::forget(AboutSectionRepository::CACHE_KEY);
    }
}
