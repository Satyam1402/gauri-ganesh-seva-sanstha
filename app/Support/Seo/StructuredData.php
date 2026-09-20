<?php

namespace App\Support\Seo;

use App\Models\BlogPost;
use App\Models\Event;
use App\Models\Faq;
use App\Models\GalleryAlbum;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * JSON-LD builders. Every builder describes only what the page visibly
 * shows and drops keys whose value is missing — nothing is invented to
 * pad the markup. Organisation data comes from Site Settings, so only
 * verified, admin-entered facts appear.
 */
class StructuredData
{
    /**
     * Organization (NGO) built from Site Settings. Returns null when even
     * the name is missing.
     *
     * @return array<string, mixed>|null
     */
    public static function organization(): ?array
    {
        $name = setting('organization.legal_name') ?? setting('general.site_name');

        if (! $name) {
            return null;
        }

        $address = array_filter([
            'streetAddress' => setting('organization.address_line'),
            'addressLocality' => setting('organization.city'),
            'addressRegion' => setting('organization.state'),
            'postalCode' => setting('organization.pin_code'),
            'addressCountry' => setting('organization.country'),
        ]);

        $socials = array_values(array_filter([
            setting('social.facebook_url'),
            setting('social.instagram_url'),
            setting('social.youtube_url'),
            setting('social.twitter_url'),
            setting('social.linkedin_url'),
            setting('social.telegram_url'),
        ]));

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            '@id' => url('/').'#organization',
            'name' => $name,
            'alternateName' => setting('organization.short_name'),
            'url' => url('/'),
            'logo' => setting_media('branding.logo', 'webp'),
            'description' => setting('organization.about_short') ?? setting('general.site_description'),
            'telephone' => setting('contact.phone_primary'),
            'email' => setting('contact.email_primary'),
            'address' => $address ? ['@type' => 'PostalAddress'] + $address : null,
            'sameAs' => $socials ?: null,
            'foundingDate' => setting('organization.established_year') ? (string) setting('organization.established_year') : null,
        ]);
    }

    /**
     * WebSite (homepage only).
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => setting('general.site_name', config('app.name')),
            'url' => url('/'),
            'publisher' => ['@id' => url('/').'#organization'],
        ]);
    }

    /**
     * Generic WebPage / CollectionPage / ContactPage / AboutPage.
     *
     * @return array<string, mixed>
     */
    public static function webPage(string $type, string $name, string $url, ?string $description = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $name,
            'url' => $url,
            'description' => $description,
            'isPartOf' => ['@id' => url('/').'#website'],
        ]);
    }

    /**
     * @param  list<array{label: string, url?: string|null}>  $crumbs
     * @return array<string, mixed>|null
     */
    public static function breadcrumbs(array $crumbs, string $currentUrl): ?array
    {
        if (count($crumbs) < 2) {
            return null;
        }

        $items = [];

        foreach (array_values($crumbs) as $index => $crumb) {
            $items[] = self::clean([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'] ?? ($index === count($crumbs) - 1 ? $currentUrl : null),
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Article for a blog post (or an activity report).
     *
     * @return array<string, mixed>
     */
    public static function article(string $type, string $headline, string $url, ?string $description, ?string $image, ?string $published, ?string $modified, ?string $authorName = null): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => $type,
            'headline' => mb_substr($headline, 0, 110),
            'description' => $description,
            'image' => $image,
            'datePublished' => $published,
            'dateModified' => $modified ?? $published,
            'author' => $authorName ? ['@type' => 'Person', 'name' => $authorName] : ['@id' => url('/').'#organization'],
            'publisher' => ['@id' => url('/').'#organization'],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function blogPost(BlogPost $post, string $url, ?string $image): array
    {
        return self::article(
            'Article',
            $post->title,
            $url,
            $post->excerpt ?? null,
            $image,
            $post->published_at?->toIso8601String(),
            $post->updated_at?->toIso8601String(),
            $post->author?->name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function event(Event $event, string $url, ?string $image): array
    {
        $status = match ($event->status->value) {
            'cancelled' => 'https://schema.org/EventCancelled',
            default => 'https://schema.org/EventScheduled',
        };

        $start = $event->start_date->copy();
        if ($event->start_time) {
            $start->setTimeFromTimeString($event->start_time);
        }
        $end = ($event->end_date ?? $event->start_date)->copy();
        if ($event->end_time) {
            $end->setTimeFromTimeString($event->end_time);
        }

        $address = array_filter([
            'streetAddress' => $event->address,
            'addressLocality' => $event->city,
            'addressRegion' => $event->state,
            'addressCountry' => setting('organization.country') ?? 'IN',
        ]);

        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => $event->short_description,
            'image' => $image,
            'url' => $url,
            'startDate' => $start->toIso8601String(),
            'endDate' => $end->toIso8601String(),
            'eventStatus' => $status,
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $event->venue || $address ? self::clean([
                '@type' => 'Place',
                'name' => $event->venue,
                'address' => $address ? ['@type' => 'PostalAddress'] + $address : null,
            ]) : null,
            'organizer' => $event->organizer
                ? ['@type' => 'Organization', 'name' => $event->organizer]
                : ['@id' => url('/').'#organization'],
            'isAccessibleForFree' => true,
        ]);
    }

    /**
     * @param  Collection<int, Media>  $photos
     * @return array<string, mixed>
     */
    public static function imageGallery(GalleryAlbum $album, string $url, Collection $photos): array
    {
        return self::clean([
            '@context' => 'https://schema.org',
            '@type' => 'ImageGallery',
            'name' => $album->title,
            'description' => $album->description ?? null,
            'url' => $url,
            'image' => $photos->map(fn ($media) => $media->getUrl())->values()->all() ?: null,
        ]);
    }

    /**
     * FAQPage — only for questions actually rendered on the page.
     *
     * @param  iterable<Faq>  $faqs
     * @return array<string, mixed>
     */
    public static function faqPage(string $name, string $url, iterable $faqs): array
    {
        $entities = [];

        foreach ($faqs as $faq) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answerText()],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'name' => $name,
            'url' => $url,
            'mainEntity' => $entities,
        ];
    }

    /**
     * Drop null / empty values recursively so no key is emitted "for SEO"
     * without a real value behind it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }

            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
