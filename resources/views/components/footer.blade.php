<footer class="bg-brand-navy text-brand-cream">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-8 md:grid-cols-3">
        <div>
            <span class="flex items-center gap-3">
                <img
                    src="{{ asset('images/logo-mark.png') }}"
                    alt=""
                    class="h-10 w-10 rounded-full object-cover"
                >
                <span class="font-brush text-4xl leading-none">Hendy &amp; Co.</span>
            </span>
            <p class="mt-3 max-w-xs text-sm text-brand-cream/70">
                Kind, patient and reliable pet pop-in visits and dog walks - treating your pets like they're my own.
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold tracking-wide text-brand-cream uppercase">Quick links</h3>
            <ul class="mt-3 space-y-2 text-sm text-brand-cream/70">
                <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                <li><a href="{{ route('pricing') }}" class="hover:text-white">Pricing</a></li>
                <li><a href="{{ route('testimonials') }}" class="hover:text-white">Testimonials</a></li>
                <li><a href="#contact" class="hover:text-white">Get in Touch</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold tracking-wide text-brand-cream uppercase">Get in touch</h3>
            <ul class="mt-3 space-y-2 text-sm text-brand-cream/70">
                <li><a href="tel:07895805449" class="hover:text-white">07895 805449</a></li>
                <li><a href="mailto:hendycopetcare@gmail.com" class="hover:text-white">hendycopetcare@gmail.com</a></li>
                <li><a href="https://facebook.com/hendyandcopetcare" target="_blank" rel="noopener" class="hover:text-white">facebook.com/hendyandcopetcare</a></li>
                <li><a href="https://instagram.com/hendyandcopetcare" target="_blank" rel="noopener" class="hover:text-white">@hendyandcopetcare</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-brand-cream/10 px-4 py-4 text-center text-xs text-brand-cream/50 sm:px-8">
        &copy; {{ now()->year }} Hendy &amp; Co. Based in Pendeen &amp; surrounding areas.
    </div>
</footer>
