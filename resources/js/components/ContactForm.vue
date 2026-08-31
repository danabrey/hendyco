<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    sourcePage: { type: String, default: null },
    csrfToken: { type: String, required: true },
    action: { type: String, required: true },
});

const form = reactive({
    name: '',
    email: '',
    phone: '',
    message: '',
});

const submitting = ref(false);
const succeeded = ref(false);
const successMessage = ref('');
const errors = ref({});

async function submit() {
    submitting.value = true;
    succeeded.value = false;
    errors.value = {};

    try {
        const response = await fetch(props.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': props.csrfToken,
            },
            body: JSON.stringify({ ...form, source_page: props.sourcePage }),
        });

        const data = await response.json();

        if (response.status === 422) {
            errors.value = data.errors ?? {};
            return;
        }

        if (!response.ok) {
            errors.value = { general: ['Something went wrong. Please try again or call/email directly.'] };
            return;
        }

        succeeded.value = true;
        successMessage.value = data.message;
        form.name = '';
        form.email = '';
        form.phone = '';
        form.message = '';
    } catch {
        errors.value = { general: ['Something went wrong. Please try again or call/email directly.'] };
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <form v-if="!succeeded" class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium text-brand-navy">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="mt-1 w-full rounded-lg border border-brand-navy/20 bg-white px-4 py-2 text-brand-navy focus:border-brand-rose focus:outline-none"
                >
                <p v-if="errors.name" class="mt-1 text-sm text-brand-rose">{{ errors.name[0] }}</p>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-brand-navy">Phone (optional)</label>
                <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    class="mt-1 w-full rounded-lg border border-brand-navy/20 bg-white px-4 py-2 text-brand-navy focus:border-brand-rose focus:outline-none"
                >
                <p v-if="errors.phone" class="mt-1 text-sm text-brand-rose">{{ errors.phone[0] }}</p>
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-brand-navy">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="mt-1 w-full rounded-lg border border-brand-navy/20 bg-white px-4 py-2 text-brand-navy focus:border-brand-rose focus:outline-none"
            >
            <p v-if="errors.email" class="mt-1 text-sm text-brand-rose">{{ errors.email[0] }}</p>
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-brand-navy">Tell me about your pet</label>
            <textarea
                id="message"
                v-model="form.message"
                rows="4"
                required
                class="mt-1 w-full rounded-lg border border-brand-navy/20 bg-white px-4 py-2 text-brand-navy focus:border-brand-rose focus:outline-none"
            ></textarea>
            <p v-if="errors.message" class="mt-1 text-sm text-brand-rose">{{ errors.message[0] }}</p>
        </div>

        <p v-if="errors.general" class="text-sm text-brand-rose">
            {{ errors.general[0] }}
        </p>

        <button
            type="submit"
            :disabled="submitting"
            class="w-full rounded-full bg-brand-rose px-6 py-3 font-medium text-white transition hover:bg-brand-rose/90 disabled:opacity-60"
        >
            {{ submitting ? 'Sending…' : 'Send message' }}
        </button>
    </form>

    <div v-else class="rounded-lg bg-brand-olive/15 px-6 py-8 text-center text-brand-navy">
        <p class="text-lg font-medium">{{ successMessage }}</p>
    </div>
</template>
