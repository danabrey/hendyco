@php
    $services = [
        [
            'name' => 'Pet Pop-In',
            'price' => '£12',
            'description' => 'A visit while you\'re at work or out for the day, to keep your pets happy, comfortable and cared for at home.',
        ],
        [
            'name' => '30-Minute Dog Walk',
            'price' => '£12',
            'description' => 'Individual walks tailored to your dog\'s needs.',
        ],
        [
            'name' => '1-Hour Dog Walk',
            'price' => '£16.50',
            'description' => 'A longer adventure with plenty of time to explore.',
        ],
    ];

    $includes = [
        [
            'title' => 'Dogs',
            'icon' => 'paw',
            'bg' => 'bg-brand-olive',
            'items' => [
                'Toilet/garden break',
                'Feeding & fresh water',
                'Play, fuss & companionship',
                'Medication if required',
                'A check that all is safe and well',
            ],
        ],
        [
            'title' => 'Cats',
            'icon' => 'cat',
            'bg' => 'bg-brand-rose',
            'items' => [
                'Feeding & fresh water',
                'Litter tray cleaned',
                'Play & fuss',
                'A check that all is safe and well',
            ],
        ],
        [
            'title' => 'Small Pets',
            'icon' => 'rabbit',
            'bg' => 'bg-brand-olive',
            'items' => [
                'Feeding & fresh water',
                'Litter/toilet areas cleaned',
                'Hay/bedding topped up',
                'Hutch/cage/enclosure checks',
                'Play, handling & companionship',
            ],
        ],
    ];
@endphp

<x-layout title="Pricing" current="pricing">
    <section class="bg-brand-navy py-16 text-center text-white">
        <div class="mx-auto max-w-3xl px-4 sm:px-8">
            <p class="text-sm font-semibold tracking-widest text-brand-cream/70 uppercase">Services &amp; Pricing</p>
            <h1 class="mt-2 font-script text-5xl">Simple, fair pricing</h1>
            <p class="mt-3 text-brand-cream/80">No hidden fees - just kind, reliable care for your pet.</p>
        </div>
    </section>

    <section class="bg-brand-cream">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-8">
            <div class="grid gap-8 md:grid-cols-3">
                @foreach ($services as $service)
                    <div class="rounded-2xl bg-white p-8 text-center shadow-sm">
                        <h3 class="text-lg font-semibold text-brand-navy">{{ $service['name'] }}</h3>
                        <p class="mt-2 text-3xl font-semibold text-brand-rose">{{ $service['price'] }}</p>
                        <p class="mt-3 text-sm text-brand-navy/70">{{ $service['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex flex-col items-center gap-1 text-center text-brand-navy/70">
                <p>Extra dogs from the same household: <span class="font-semibold text-brand-navy">+£3</span></p>
                <p>Puppy visits available - pricing on request.</p>
            </div>
        </div>
    </section>

    <section class="bg-white">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-8">
            <div class="text-center">
                <p class="text-sm font-semibold tracking-widest text-brand-rose uppercase">Pet Pop-In Includes</p>
                <h2 class="mt-2 font-script text-4xl text-brand-navy">Every pet, looked after</h2>
            </div>

            <div class="mt-12 grid gap-8 md:grid-cols-3">
                @foreach ($includes as $group)
                    <div class="rounded-2xl bg-brand-cream p-6">
                        <span class="flex h-12 w-12 items-center justify-center rounded-full {{ $group['bg'] }} text-white">
                            <x-icon :name="$group['icon']" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-4 text-lg font-semibold text-brand-navy">{{ $group['title'] }}</h3>
                        <ul class="mt-4 space-y-2 text-sm text-brand-navy/80">
                            @foreach ($group['items'] as $item)
                                <li class="flex items-start gap-2">
                                    <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand-olive" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-brand-rose">
        <div class="mx-auto max-w-4xl px-4 py-12 text-center text-white sm:px-8">
            <x-icon name="heart" class="mx-auto h-8 w-8" />
            <h2 class="mt-2 font-script text-3xl">Free Meet &amp; Greet</h2>
            <p class="mt-2 text-white/90">Let's get to know each other and make sure your pet feels happy and at ease.</p>
        </div>
    </section>

    <x-contact-form source-page="pricing" />
</x-layout>
