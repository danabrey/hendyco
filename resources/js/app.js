import { createApp } from 'vue';
import * as Sentry from '@sentry/vue';
import ContactForm from './components/ContactForm.vue';

const islands = {
    'contact-form': ContactForm,
};

const sentryOptions = {
    dsn: 'https://23e7a8cc40c4833f7427e209d32ee8de@o1110221.ingest.us.sentry.io/4512351893585920',
    environment: import.meta.env.MODE,
    integrations: [Sentry.browserTracingIntegration()],
    tracesSampleRate: 1.0,
    sendDefaultPii: false,
};

if (import.meta.env.PROD) {
    Sentry.init(sentryOptions);
}

document.querySelectorAll('[data-vue-island]').forEach((el) => {
    const component = islands[el.dataset.vueIsland];

    if (!component) {
        return;
    }

    const app = createApp(component, { ...el.dataset });

    Sentry.attachErrorHandler(app, { logErrors: true });

    app.mount(el);
});

document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
    const target = document.getElementById(button.getAttribute('aria-controls'));

    if (!target) {
        return;
    }

    button.addEventListener('click', () => {
        const isOpen = target.classList.toggle('flex');
        target.classList.toggle('hidden', !isOpen);
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
});
