import './bootstrap';

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

Alpine.plugin(focus);

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        localStorage.theme = this.dark ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', this.dark);
    },
});

window.Alpine = Alpine;

Alpine.start();
