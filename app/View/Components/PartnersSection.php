<?php

namespace App\View\Components;

use App\Interfaces\PartnerRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * Reusable public partners block (<x-partners-section />), used on the
 * homepage, About, Donate and Activities pages.
 *
 * Class-based (rather than anonymous) because it resolves its own data
 * through the repository — keeping queries out of Blade while letting any
 * page drop the section in with one tag.
 */
class PartnersSection extends Component
{
    private ?Collection $items = null;

    public function __construct(
        private PartnerRepositoryInterface $partners,
        public string $heading = 'Our Partners & Sponsors',
        public ?string $subheading = null,
        /** Restrict to one partner-type slug, e.g. "sponsor". */
        public ?string $type = null,
        /** Only featured partners. */
        public bool $featured = false,
        public int $limit = 8,
        /** When the type/featured filter yields nothing, fall back to the general list. */
        public bool $fallback = true,
        /** Render nothing at all when there is nothing to show. */
        public bool $hideWhenEmpty = true,
        /** "logos" = compact logo wall; "cards" = logo + name + description. */
        public string $variant = 'logos',
        /** x-ui.section background variant. */
        public string $background = 'white',
        /** Show the "See all partners" link to the full page. */
        public bool $showLink = true,
    ) {}

    public function shouldRender(): bool
    {
        return ! $this->hideWhenEmpty || $this->items()->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.partners-section', [
            'items' => $this->items(),
            'linkUrl' => $this->type
                ? route('partners.index', ['type' => $this->type])
                : route('partners.index'),
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

        $items = $this->partners->sectionList($this->type, $this->featured, $this->limit);

        // Type has no active partners yet → featured ones from anywhere.
        if ($items->isEmpty() && $this->fallback && $this->type !== null) {
            $items = $this->partners->sectionList(null, true, $this->limit);
        }

        // Nothing featured yet either → any active partners.
        if ($items->isEmpty() && $this->fallback) {
            $items = $this->partners->sectionList(null, false, $this->limit);
        }

        return $this->items = $items;
    }
}
