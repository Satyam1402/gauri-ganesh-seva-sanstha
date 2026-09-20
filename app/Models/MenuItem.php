<?php

namespace App\Models;

use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;

class MenuItem extends Model
{
    /**
     * Named routes an admin may link to. Whitelisted so the navigation
     * editor can never point at admin/auth/callback routes.
     *
     * @var array<string, string>
     */
    public const LINKABLE_ROUTES = [
        'home' => 'Home',
        'about' => 'About Us',
        'activities.index' => 'Activities',
        'donations.campaigns.index' => 'Campaigns',
        'donations.donate' => 'Donate',
        'events.index' => 'Events',
        'gallery.index' => 'Gallery',
        'blog.index' => 'Blog',
        'testimonials.index' => 'Testimonials',
        'partners.index' => 'Partners & Sponsors',
        'faq.index' => 'FAQ',
        'volunteer.create' => 'Become a Volunteer',
        'contact' => 'Contact Us',
        'legal.show.privacy-policy' => 'Privacy Policy',
        'legal.show.terms' => 'Terms & Conditions',
        'legal.show.donation-terms' => 'Donation Terms',
        'legal.show.refund-policy' => 'Refund Policy',
        'legal.show.volunteer-terms' => 'Volunteer Terms',
    ];

    protected $fillable = [
        'location',
        'parent_id',
        'label',
        'link_type',
        'route_name',
        'url',
        'open_in_new_tab',
        'is_active',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'location' => MenuLocation::class,
            'link_type' => MenuLinkType::class,
            'open_in_new_tab' => 'boolean',
            'is_active' => 'boolean',
            'order_column' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_column');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForLocation(Builder $query, MenuLocation|string $location): Builder
    {
        return $query->where('location', $location instanceof MenuLocation ? $location->value : $location);
    }

    /**
     * Resolved href. Routes are looked up by name (a deleted route yields
     * "#" rather than an exception); paths and external URLs are stored
     * pre-validated.
     */
    public function href(): string
    {
        return match ($this->link_type) {
            MenuLinkType::Route => $this->route_name && Route::has($this->route_name) ? route($this->route_name) : '#',
            default => $this->url ?: '#',
        };
    }

    /**
     * Whether this item (or a child) matches the current request, for
     * aria-current / active styling.
     */
    public function isCurrent(): bool
    {
        if ($this->link_type === MenuLinkType::Route && $this->route_name) {
            $base = explode('.', $this->route_name)[0];

            return request()->routeIs($this->route_name) || request()->routeIs($base.'.*');
        }

        if ($this->link_type === MenuLinkType::Path && $this->url) {
            return request()->is(ltrim($this->url, '/')) || request()->is(ltrim($this->url, '/').'/*');
        }

        return false;
    }

    public function hasChildren(): bool
    {
        return $this->relationLoaded('children') ? $this->children->isNotEmpty() : $this->children()->exists();
    }
}
