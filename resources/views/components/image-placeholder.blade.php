@props(['src', 'alt' => '', 'label' => null])

@if (file_exists(public_path($src)))
    <img src="{{ asset($src) }}" alt="{{ $alt }}" {{ $attributes }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center border-2 border-dashed border-brand-navy/25 bg-brand-navy/5 p-4 text-center text-xs text-brand-navy/50']) }}>
        <span>{{ $label ?? ($alt ?: 'Photo') }}<br>public/{{ $src }}</span>
    </div>
@endif
