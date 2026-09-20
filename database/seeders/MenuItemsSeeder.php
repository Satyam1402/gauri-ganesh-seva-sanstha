<?php

namespace Database\Seeders;

use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use App\Models\MenuItem;
use App\Repositories\MenuItemRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class MenuItemsSeeder extends Seeder
{
    /**
     * Default navigation — the links the header and footer used to
     * hard-code, now editable. Runs only when a location is empty so
     * admin changes are never clobbered.
     */
    public function run(): void
    {
        $menus = [
            MenuLocation::Header->value => [
                ['About', 'about'],
                ['Activities', 'activities.index'],
                ['Campaigns', 'donations.campaigns.index'],
                ['Events', 'events.index'],
                ['Gallery', 'gallery.index'],
                ['Blog', 'blog.index'],
                ['Contact', 'contact'],
            ],
            MenuLocation::FooterExplore->value => [
                ['About', 'about'],
                ['Activities', 'activities.index'],
                ['Campaigns', 'donations.campaigns.index'],
                ['Events', 'events.index'],
                ['Blog', 'blog.index'],
                ['Testimonials', 'testimonials.index'],
                ['Partners', 'partners.index'],
            ],
            MenuLocation::FooterInvolved->value => [
                ['Donate', 'donations.donate'],
                ['Volunteer', 'volunteer.create'],
                ['FAQ', 'faq.index'],
                ['Contact', 'contact'],
            ],
        ];

        foreach ($menus as $location => $items) {
            if (MenuItem::query()->forLocation($location)->exists()) {
                continue;
            }

            foreach ($items as $order => [$label, $route]) {
                MenuItem::create([
                    'location' => $location,
                    'label' => $label,
                    'link_type' => MenuLinkType::Route->value,
                    'route_name' => $route,
                    'is_active' => true,
                    'order_column' => $order,
                ]);
            }
        }

        foreach (MenuLocation::cases() as $case) {
            Cache::forget(MenuItemRepository::cacheKey($case));
        }
    }
}
