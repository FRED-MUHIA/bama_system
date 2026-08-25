@props([
    'mobileLabel' => 'Records',
])

<div {{ $attributes->merge(['class' => 'responsive-table']) }}>
    <div class="responsive-table-scroll desktop-table">
        {{ $slot }}
    </div>

    @isset($mobile)
        <div class="mobile-record-list" aria-label="{{ $mobileLabel }}">
            {{ $mobile }}
        </div>
    @endisset
</div>
