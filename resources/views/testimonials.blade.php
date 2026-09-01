@php
    $testimonials = [
        [
            'quote' => "I'm always reassured when I leave my two nervous and sassy cats under your care! ❤️",
            'name' => 'Jenny Dines',
            'location' => 'Pendeen',
        ],
    ];
@endphp

<x-layout title="Testimonials" current="testimonials">
    <section class="bg-brand-navy py-16 text-center text-white">
        <div class="mx-auto max-w-3xl px-4 sm:px-8">
            <p class="text-sm font-semibold tracking-widest text-brand-cream/70 uppercase">Testimonials</p>
            <h1 class="mt-2 font-script text-5xl">What pet parents say</h1>
            <p class="mt-3 text-brand-cream/80">Real feedback from Hendy &amp; Co clients.</p>
        </div>
    </section>

    <section class="bg-brand-cream">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-8">
            <div class="grid gap-8 {{ count($testimonials) > 1 ? 'md:grid-cols-3' : 'mx-auto max-w-md' }}">
                @foreach ($testimonials as $testimonial)
                    <div class="rounded-2xl bg-white p-8 shadow-sm">
                        <div class="flex gap-1 text-brand-rose">
                            @for ($star = 0; $star < 5; $star++)
                                <x-icon name="star" class="h-4 w-4" fill="currentColor" />
                            @endfor
                        </div>
                        <p class="mt-4 text-brand-navy/80 italic">&ldquo;{{ $testimonial['quote'] }}&rdquo;</p>
                        <p class="mt-4 text-sm font-semibold text-brand-navy">{{ $testimonial['name'] }}</p>
                        <p class="text-xs text-brand-navy/50">{{ $testimonial['location'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-sm text-brand-navy/60">
                Please send us your reviews. We'd love to show them here!
            </p>
        </div>
    </section>

    <x-contact-form source-page="testimonials" />
</x-layout>
