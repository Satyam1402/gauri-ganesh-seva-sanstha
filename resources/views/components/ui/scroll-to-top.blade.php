<button
    type="button"
    x-data="{ visible: false }"
    x-show="visible"
    x-cloak
    x-transition
    @scroll.window="visible = window.scrollY > 400"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    class="fixed bottom-6 right-6 z-30 flex h-11 w-11 items-center justify-center rounded-full bg-primary-700 text-white shadow-lg transition hover:bg-primary-800"
    aria-label="Scroll to top"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
    </svg>
</button>
