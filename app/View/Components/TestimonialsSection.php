<?php

namespace App\View\Components;

use App\Enums\TestimonialType;
use App\Interfaces\TestimonialRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * Reusable public testimonials block (<x-testimonials-section />), used on
 * the homepage, About page, activity and campaign pages.
 *
 * This is a class-based component (rather than an anonymous one) because
 * it resolves its own data through the repository — keeping queries out of
 * Blade while letting any page drop the section in with one tag.
 */
class TestimonialsSection extends Component
{
    private ?Collection $items = null;

    public function __construct(
        private TestimonialRepositoryInterface $testimonials,
        public string $heading = 'Voices From Our Community',
        public ?string $subheading = null,
        /** Restrict to one TestimonialType value, e.g. "donor". */
        public ?string $type = null,
        /** Only featured testimonials. */
        public bool $featured = false,
        public int $limit = 3,
        /** An Activity or DonationCampaign — its own linked testimonials come first. */
        public ?Model $for = null,
        /** When $for has no linked testimonials, fall back to the general list. */
        public bool $fallback = true,
        /** Render nothing at all when there is nothing to show. */
        public bool $hideWhenEmpty = true,
        /** x-ui.section background variant. */
        public string $background = 'muted',
        /** Show the "Read more stories" link to the full page. */
        public bool $showLink = true,
    ) {}

    public function shouldRender(): bool
    {
        return ! $this->hideWhenEmpty || $this->items()->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.testimonials-section', [
            'items' => $this->items(),
            'typeLabel' => $this->type ? TestimonialType::tryFrom($this->type)?->pluralLabel() : null,
        ]);
    }

    /**
     * Resolved once; shouldRender() and render() both need it.
     */
    private function items(): Collection
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $items = $this->for !== null
            ? $this->testimonials->forRelated($this->for, $this->limit)
            : new Collection;

        if ($items->isEmpty() && ($this->for === null || $this->fallback)) {
            $items = $this->testimonials->sectionList($this->type, $this->featured, $this->limit);
        }

        // Nothing featured yet (e.g. a fresh site) — show the latest
        // published entries rather than an empty section.
        if ($items->isEmpty() && $this->featured && $this->fallback) {
            $items = $this->testimonials->sectionList($this->type, false, $this->limit);
        }

        return $this->items = $items;
    }
}
