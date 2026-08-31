import { createApp } from 'vue';
import ContactForm from './components/ContactForm.vue';

const islands = {
    'contact-form': ContactForm,
};

document.querySelectorAll('[data-vue-island]').forEach((el) => {
    const component = islands[el.dataset.vueIsland];

    if (!component) {
        return;
    }

    createApp(component, { ...el.dataset }).mount(el);
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
