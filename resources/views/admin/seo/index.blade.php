@extends('layouts.admin')

@section('title', 'SEO')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'SEO']]" />
@endsection

@section('content')
    @if ($discourage)
        <x-ui.alert variant="warning" class="mb-6">
            <strong>Search engines are discouraged.</strong> Every page is <code>noindex</code>, robots.txt disallows everything and the sitemap is off. Turn this off under
            <a href="{{ route('admin.settings.edit', 'seo') }}" class="font-medium underline">Settings → SEO</a> before launch.
        </x-ui.alert>
    @endif

    {{-- Global defaults --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Global Defaults</h2>
                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Inherited by every page that does not set its own values.</p>
                </div>
                <x-ui.button href="{{ route('admin.settings.edit', 'seo') }}" variant="secondary" size="sm">Edit Defaults</x-ui.button>
            </div>
            <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                <div><dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Title suffix</dt><dd class="mt-0.5 text-text-900 dark:text-night-text">… {{ $globals['separator'] }} {{ $globals['suffix'] }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Default robots</dt><dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $globals['robots'] }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Default description</dt><dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $globals['description'] }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Default share image</dt><dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $globals['image'] ? 'Set' : 'Not set — upload one under Settings → Branding' }}</dd></div>
                <div><dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">X/Twitter handle</dt><dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $globals['twitter_site'] ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Crawler Endpoints</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex items-center justify-between gap-3">
                    <a href="{{ route('sitemap') }}" target="_blank" rel="noopener" class="text-primary-700 hover:underline dark:text-night-text">/sitemap.xml ↗</a>
                    <x-ui.badge :variant="$sitemapEnabled && ! $discourage ? 'success' : 'neutral'">{{ $sitemapEnabled && ! $discourage ? 'Published' : 'Off' }}</x-ui.badge>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <a href="{{ route('robots') }}" target="_blank" rel="noopener" class="text-primary-700 hover:underline dark:text-night-text">/robots.txt ↗</a>
                    <x-ui.badge :variant="$discourage ? 'warning' : 'success'">{{ $discourage ? 'Blocks all' : 'Allows public' }}</x-ui.badge>
                </li>
                @foreach ($verification as $engine => $configured)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-text-600 dark:text-night-text-muted">{{ $engine }} verification</span>
                        <x-ui.badge :variant="$configured ? 'success' : 'neutral'">{{ $configured ? 'Set' : 'Not set' }}</x-ui.badge>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('admin.seo.refresh-sitemap') }}" class="mt-4">
                @csrf
                <x-ui.button type="submit" variant="secondary" size="sm" class="w-full">Refresh Sitemap Cache</x-ui.button>
            </form>
        </x-ui.card>
    </div>

    {{-- Static pages --}}
    <x-ui.card class="mb-6">
        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Site Pages</h2>
        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Pages without custom metadata use their content and the global defaults — that is fine; set custom values only where they read better.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle text-xs uppercase tracking-wide text-text-400 dark:border-night-border dark:text-night-text-muted">
                    <tr>
                        <th scope="col" class="py-2 pr-4 font-medium">Page</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Metadata</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Robots</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Structured data</th>
                        <th scope="col" class="py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle dark:divide-night-border">
                    @foreach ($staticPages as $item)
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="font-medium text-text-900 dark:text-night-text">{{ $item['title'] }}</span>
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="block text-xs text-text-400 hover:underline dark:text-night-text-muted">{{ $item['url'] }}</a>
                            </td>
                            <td class="py-3 pr-4">
                                <x-ui.badge :variant="$item['status'] === 'custom' ? 'success' : 'neutral'">{{ ['custom' => 'Custom', 'defaults' => 'Inherited', 'missing' => 'Not seeded'][$item['status']] }}</x-ui.badge>
                            </td>
                            <td class="py-3 pr-4 text-text-600 dark:text-night-text-muted">{{ $item['robots'] ?? 'inherit' }}</td>
                            <td class="py-3 pr-4 text-xs text-text-600 dark:text-night-text-muted">{{ $item['schema'] }}</td>
                            <td class="py-3 text-right">
                                @if ($item['page'])
                                    <a href="{{ route('admin.pages.seo.edit', $item['page']) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit SEO</a>
                                @else
                                    <span class="text-xs text-text-400 dark:text-night-text-muted">Run PagesSeeder</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Content coverage --}}
    <x-ui.card class="mb-6">
        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Content SEO Coverage</h2>
        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Each item's SEO is edited on its own form. Counts cover published, publicly visible content only.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle text-xs uppercase tracking-wide text-text-400 dark:border-night-border dark:text-night-text-muted">
                    <tr>
                        <th scope="col" class="py-2 pr-4 font-medium">Content type</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Published</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Custom description</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Marked noindex</th>
                        <th scope="col" class="py-2"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle dark:divide-night-border">
                    @foreach ($contentTypes as $type)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-text-900 dark:text-night-text">{{ $type['label'] }}</td>
                            <td class="py-3 pr-4 text-text-600 dark:text-night-text-muted">{{ $type['total'] }}</td>
                            <td class="py-3 pr-4 text-text-600 dark:text-night-text-muted">{{ $type['with_meta'] }} / {{ $type['total'] }}</td>
                            <td class="py-3 pr-4 text-text-600 dark:text-night-text-muted">{{ $type['noindex'] }}</td>
                            <td class="py-3 text-right"><a href="{{ route($type['route']) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Manage</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Redirects --}}
    <x-ui.card>
        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Slug Redirects <span class="text-sm font-normal text-text-400 dark:text-night-text-muted">({{ $redirectCount }})</span></h2>
        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Created automatically whenever a published item's slug changes, so old links keep working with a permanent redirect.</p>
        @if ($redirects->isEmpty())
            <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No slugs have been changed yet.</p>
        @else
            <ul class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                @foreach ($redirects as $redirect)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="font-mono text-xs text-text-600 dark:text-night-text-muted">{{ class_basename($redirect->model_type) }}: /{{ $redirect->old_slug }} → /{{ $redirect->new_slug }}</span>
                        <span class="text-xs text-text-400 dark:text-night-text-muted">{{ $redirect->created_at->format(setting('general.date_format', 'd M Y')) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
@endsection
