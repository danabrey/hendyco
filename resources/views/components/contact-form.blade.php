@props(['sourcePage'])

<section id="contact" class="bg-brand-olive/10">
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-8">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-widest text-brand-rose uppercase">Get in touch</p>
            <h2 class="mt-2 font-script text-4xl text-brand-navy">Let's meet your pet</h2>
            <p class="mt-3 text-brand-navy/70">
                Free meet &amp; greet included - tell me a bit about you and your pet and I'll get back to you soon.
            </p>
        </div>

        <div
            class="mt-8"
            data-vue-island="contact-form"
            data-source-page="{{ $sourcePage }}"
            data-csrf-token="{{ csrf_token() }}"
            data-action="{{ route('contact.store') }}"
        ></div>
    </div>
</section>
