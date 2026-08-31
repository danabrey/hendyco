@php
    $careIncludes = [
        ['icon' => 'toilet', 'label' => 'Toilet Break & Short Walk', 'bg' => 'bg-brand-olive'],
        ['icon' => 'bowl', 'label' => 'Feeding & Fresh Water', 'bg' => 'bg-brand-rose'],
        ['icon' => 'ball', 'label' => 'Playtime & Cuddles', 'bg' => 'bg-brand-olive'],
        ['icon' => 'camera', 'label' => 'Photo Updates', 'bg' => 'bg-brand-rose'],
        ['icon' => 'medication', 'label' => 'Medication (if needed)', 'bg' => 'bg-brand-olive'],
    ];

    $services = [
        [
            'slug' => 'pop-in',
            'icon' => 'house',
            'name' => 'Pet Pop-In',
            'price' => '£12',
            'description' => 'A visit while you\'re at work or out for the day, to keep your pets happy, comfortable and cared for at home.',
        ],
        [
            'slug' => 'short-walk',
            'icon' => 'walk',
            'name' => '30-Minute Dog Walk',
            'price' => '£12',
            'description' => 'Individual walks tailored to your dog\'s needs.',
        ],
        [
            'slug' => 'long-walk',
            'icon' => 'walk',
            'name' => '1-Hour Dog Walk',
            'price' => '£16.50',
            'description' => 'A longer adventure with plenty of time to explore.',
        ],
    ];
@endphp

<x-layout title="Home" current="home">
    <section class="relative overflow-hidden bg-brand-navy">
        <x-image-placeholder
            src="images/hero.jpg"
            alt="A happy dog out on a walk"
            class="absolute inset-0 h-full w-full object-cover opacity-30"
        />

        <div class="relative mx-auto max-w-4xl px-4 pt-12 pb-24 text-center sm:px-8 sm:pt-16 sm:pb-32">
            <div class="mx-auto mb-8 flex h-54 w-54 items-center justify-center rounded-full bg-brand-cream p-3 shadow-lg sm:h-66 sm:w-66">
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="Hendy & Co"
                    class="h-full w-full object-contain"
                >
            </div>
            <p class="mt-4 text-sm font-semibold tracking-widest text-brand-cream/80 uppercase">
                Pendeen &amp; surrounding areas &middot; Dog walking &amp; pet sitting
            </p>
            <h1 class="mt-4 font-script text-5xl text-white sm:text-6xl">Pet care for every personality</h1>
            <p class="mt-4 text-lg text-brand-cream/90">Kind, patient and reliable care for every pet, every time.</p>

            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a
                    href="#services"
                    class="rounded-full border border-white/70 px-6 py-3 font-medium text-white transition hover:bg-white/10"
                >
                    Browse services
                </a>
                <a
                    href="#contact"
                    class="rounded-full bg-brand-rose px-6 py-3 font-medium text-white transition hover:bg-brand-rose/90"
                >
                    Get in touch
                </a>
            </div>
        </div>
    </section>

    <section class="border-b border-brand-navy/10 bg-white">
        <div class="mx-auto grid max-w-6xl grid-cols-2 gap-8 px-4 py-10 text-center sm:px-8 md:grid-cols-5">
            @foreach ($careIncludes as $item)
                <div>
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $item['bg'] }} text-white">
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                    </span>
                    <p class="mt-2 text-sm font-medium text-brand-navy">{{ $item['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-brand-cream">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-8 md:grid-cols-2">
            <div>
                <p class="text-sm font-semibold tracking-widest text-brand-rose uppercase">About</p>
                <h2 class="mt-2 font-script text-4xl text-brand-navy">We're here for you</h2>
                <p class="mt-4 text-brand-navy/80">
                    Hendy &amp; Co gives your pets kind, patient and reliable care while you're away - whether
                    that's a quick pop-in or a longer adventure walk. Every visit is tailored to your pet's
                    personality, with photo updates so you know they're safe and happy.
                </p>
                <div class="mt-6 flex items-start gap-3 rounded-xl bg-brand-rose/10 p-4">
                    <x-icon name="heart" class="mt-0.5 h-5 w-5 shrink-0 text-brand-rose" />
                    <p class="text-sm text-brand-navy/80">
                        We're experienced with nervous &amp; reactive dogs and nervous or anxious cats.
                        <span class="font-medium text-brand-navy">All dogs welcome.</span>
                    </p>
                </div>
            </div>

            <div>
                <ul class="flex flex-wrap justify-center gap-6 sm:justify-start">
                    @foreach (['Kind', 'Patient', 'Reliable'] as $value)
                        <li class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-olive text-white">
                                <x-icon name="check" class="h-4 w-4" />
                            </span>
                            <span class="font-medium text-brand-navy">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
                <x-image-placeholder
                    src="images/about.jpg"
                    alt="Hendy & Co pet care"
                    class="mt-6 aspect-[4/3] w-full rounded-2xl object-cover"
                />
            </div>
        </div>
    </section>

    <section id="services" class="bg-brand-navy/5">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-8">
            <div class="text-center">
                <p class="text-sm font-semibold tracking-widest text-brand-rose uppercase">Services</p>
                <h2 class="mt-2 font-script text-4xl text-brand-navy">How can we help?</h2>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                @foreach ($services as $service)
                    <div class="rounded-2xl bg-white p-6 text-center shadow-sm">
                        <x-image-placeholder
                            src="images/service-{{ $service['slug'] }}.jpg"
                            :alt="$service['name']"
                            class="aspect-[4/3] w-full rounded-xl object-cover"
                        />
                        <h3 class="mt-4 text-lg font-semibold text-brand-navy">{{ $service['name'] }}</h3>
                        <p class="mt-1 text-2xl font-semibold text-brand-rose">{{ $service['price'] }}</p>
                        <p class="mt-2 text-sm text-brand-navy/70">{{ $service['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-8 text-center text-sm text-brand-navy/60">
                Extra dogs from the same household: +£3 &middot; Puppy visits available, pricing on request.
            </p>

            <div class="mt-6 text-center">
                <a href="{{ route('pricing') }}" class="font-medium text-brand-rose hover:underline">
                    See full pricing &amp; what's included &rarr;
                </a>
            </div>
        </div>
    </section>

    <x-contact-form source-page="home" />
</x-layout>
