@props(['current' => null])

@php
    $links = [
        'home' => ['label' => 'Home', 'route' => 'home'],
        'pricing' => ['label' => 'Pricing', 'route' => 'pricing'],
        'testimonials' => ['label' => 'Testimonials', 'route' => 'testimonials'],
    ];
@endphp

<header class="bg-brand-cream">
    <div class="border-b border-brand-navy/10 bg-brand-navy px-4 py-2 text-center text-sm text-brand-cream sm:px-8">
        <a href="tel:07895805449" class="hover:underline">07895 805449</a>
        <span class="mx-2 text-brand-cream/40">&middot;</span>
        <a href="mailto:hendycopetcare@gmail.com" class="hover:underline">hendycopetcare@gmail.com</a>
        <span class="mx-2 text-brand-cream/40">&middot;</span>
        <span>Based in Pendeen &amp; surrounding areas</span>
    </div>

    <div class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img
                src="{{ asset('images/logo-mark.png') }}"
                alt=""
                class="h-11 w-11 rounded-full object-cover"
            >
            <span class="flex flex-col">
                <span class="font-brush text-4xl leading-none text-brand-navy">Hendy &amp; Co.</span>
                <span class="hidden text-xs tracking-wide text-brand-olive uppercase sm:inline">Pet care for every personality</span>
            </span>
        </a>

        <nav class="hidden items-center gap-8 text-sm font-medium text-brand-navy md:flex">
            @foreach ($links as $key => $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="transition hover:text-brand-rose {{ $current === $key ? 'text-brand-rose' : '' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
            <a
                href="#contact"
                class="rounded-full bg-brand-rose px-5 py-2 text-white transition hover:bg-brand-rose/90"
            >
                Get in Touch
            </a>
        </nav>

        <button
            type="button"
            data-nav-toggle
            aria-controls="mobile-nav"
            aria-expanded="false"
            aria-label="Toggle menu"
            class="cursor-pointer text-brand-navy md:hidden"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <nav id="mobile-nav" class="absolute inset-x-0 top-full hidden flex-col gap-1 border-t border-brand-navy/10 bg-brand-cream px-4 py-4 text-brand-navy md:hidden">
            @foreach ($links as $key => $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="rounded-lg px-3 py-2 font-medium transition hover:bg-brand-navy/5 {{ $current === $key ? 'text-brand-rose' : '' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
            <a href="#contact" class="mt-1 rounded-full bg-brand-rose px-3 py-2 text-center text-white">
                Get in Touch
            </a>
        </nav>
    </div>
</header>
