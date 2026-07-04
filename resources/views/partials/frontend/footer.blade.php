<footer class="bg-primary-800 text-text-inverse">
    <div class="mx-auto grid max-w-[1360px] grid-cols-1 gap-10 px-4 py-16 sm:px-6 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <p class="font-display text-lg font-semibold">{{ config('app.name') }}</p>
            <p class="mt-3 text-sm text-white/70">
                Restoring dignity through food, education, medical care, and seva.
            </p>
            {{-- Trust/registration badges populate here once Org Profile module ships. --}}
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Explore</p>
            <nav class="mt-4 flex flex-col gap-2 text-sm text-white/80">
                <a href="{{ route('about') }}" class="hover:text-white">About</a>
                <a href="#" class="hover:text-white">Programs</a>
                <a href="#" class="hover:text-white">Campaigns</a>
                <a href="#" class="hover:text-white">Blog</a>
            </nav>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Get Involved</p>
            <nav class="mt-4 flex flex-col gap-2 text-sm text-white/80">
                <a href="#" class="hover:text-white">Donate</a>
                <a href="#" class="hover:text-white">Volunteer</a>
                <a href="#" class="hover:text-white">Request Help</a>
                <a href="#" class="hover:text-white">FAQ</a>
            </nav>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/60">Contact</p>
            <div class="mt-4 flex flex-col gap-2 text-sm text-white/80">
                <span>Pune, Maharashtra, India</span>
                <span>+91 00000 00000</span>
                <span>contact@ggss.org</span>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-[1360px] flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-white/60 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="flex gap-4">
                <a href="#" class="hover:text-white">Privacy Policy</a>
                <a href="#" class="hover:text-white">Terms</a>
                <a href="#" class="hover:text-white">Donation Refund Policy</a>
            </div>
        </div>
    </div>
</footer>
