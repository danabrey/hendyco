@props(['name'])

<svg {{ $attributes->merge(['class' => 'h-6 w-6', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.75']) }} xmlns="http://www.w3.org/2000/svg">
    @switch($name)
        @case('paw')
            <circle cx="12" cy="15" r="4.5" />
            <circle cx="5.5" cy="9" r="2" />
            <circle cx="10.5" cy="5.5" r="2" />
            <circle cx="15.5" cy="5.5" r="2" />
            <circle cx="19" cy="9.5" r="2" />
            @break

        @case('toilet')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h9v6a4.5 4.5 0 0 1-9 0V3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 15v2a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2" />
            <path stroke-linecap="round" d="M9 21h6" />
            @break

        @case('bowl')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 11h17a1 1 0 0 1 1 1.1 8 8 0 0 1-19 0 1 1 0 0 1 1-1.1Z" />
            <path stroke-linecap="round" d="M8 11V7m4 4V5m4 6V7" />
            @break

        @case('ball')
            <circle cx="12" cy="12" r="8.5" />
            <path stroke-linecap="round" d="M12 3.5v17M4.4 7.5c2 1.4 4.8 2.2 7.6 2.2s5.6-.8 7.6-2.2M4.4 16.5c2-1.4 4.8-2.2 7.6-2.2s5.6.8 7.6 2.2" />
            @break

        @case('camera')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z" />
            <circle cx="12" cy="13" r="3.25" />
            @break

        @case('medication')
            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
            @break

        @case('heart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-9.3-8.8C1.3 8 2.7 5 6 5c2 0 3.4 1.1 4 2.2C10.6 6.1 12 5 14 5c3.3 0 4.7 3 3.3 6.2C15 15.6 12 20 12 20Z" />
            @break

        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
            @break

        @case('house')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1v-8.5Z" />
            @break

        @case('walk')
            <circle cx="15" cy="4.5" r="1.75" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 8 9 10l-2 6M13 8l3 3 3-1M12 11l1.5 3-1 5M9.5 16l-3 1.5" />
            @break

        @case('cat')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 9 7 4l1 4h8l1-4 3 5v8a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V9Z" />
            <circle cx="9" cy="13.5" r="0.6" fill="currentColor" stroke="none" />
            <circle cx="15" cy="13.5" r="0.6" fill="currentColor" stroke="none" />
            @break

        @case('star')
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.5 2.5 5.3 5.8.7-4.3 4 1.1 5.8-5.1-2.9-5.1 2.9 1.1-5.8-4.3-4 5.8-.7L12 3.5Z" />
            @break

        @case('rabbit')
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 10c-1-3 .3-6.5 1.8-6.5s1.7 3.9 1.4 6M13.3 9.5c0-2.1 1-5.5 2.4-5.5s1.7 4.4.8 6.2" />
            <ellipse cx="12" cy="15" rx="5" ry="5.5" />
            @break
    @endswitch
</svg>
