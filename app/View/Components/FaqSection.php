<?php

namespace App\View\Components;

use App\Interfaces\FaqRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

/**
 * Reusable public FAQ block (<x-faq-section />), used on the homepage,
 * About, Donate, Volunteer, Activities and Contact pages.
 *
 * Class-based (rather than anonymous) because it resolves its own data
 * through the repository — keeping queries out of Blade while letting any
 * page drop the section in with one tag.
 */
class FaqSection extends Component
{
    private ?Collection $items = null;

    public function __construct(
        private FaqRepositoryInterface $faqs,
        public string $heading = 'Frequently Asked Questions',
        public ?string $subheading = null,
        /** Restrict to one category slug, e.g. "donations". */
        public ?string $category = null,
        /** Only featured FAQs. */
        public bool $featured = false,
        public int $limit = 6,
        /** When the category/featured filter yields nothing, fall back to the general list. */
        public bool $fallback = true,
        /** Render nothing at all when there is nothing to show. */
        public bool $hideWhenEmpty = true,
        /** x-ui.section background variant. */
        public string $background = 'white',
        /** Show the "See all FAQs" link to the full page. */
        public bool $showLink = true,
        /** Unique prefix so several sections on one page don't collide on ids. */
        public string $idPrefix = 'faq',
    ) {}

    public function shouldRender(): bool
    {
        return ! $this->hideWhenEmpty || $this->items()->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.faq-section', [
            'items' => $this->items(),
            'linkUrl' => $this->category
                ? route('faq.index', ['category' => $this->category])
                : route('faq.index'),
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

        $items = $this->faqs->sectionList($this->category, $this->featured, $this->limit);

        // Category has no published FAQs yet → featured ones from anywhere.
        if ($items->isEmpty() && $this->fallback && $this->category !== null) {
            $items = $this->faqs->sectionList(null, true, $this->limit);
        }

        // Nothing featured yet either → latest published.
        if ($items->isEmpty() && $this->fallback) {
            $items = $this->faqs->sectionList(null, false, $this->limit);
        }

        return $this->items = $items;
    }
}
