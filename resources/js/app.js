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
