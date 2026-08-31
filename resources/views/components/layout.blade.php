@props([
    'title',
    'description' => "Kind, patient and reliable pet pop-in visits and dog walks in Pendeen and surrounding areas.",
    'current' => null,
])

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">

        <title>{{ $title }} &middot; Hendy &amp; Co</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-brand-cream font-sans text-brand-navy antialiased">
        <x-nav :current="$current" />

        <main>
            {{ $slot }}
        </main>

        <x-footer />
    </body>
</html>
