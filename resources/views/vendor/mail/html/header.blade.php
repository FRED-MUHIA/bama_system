@props(['url'])
@php
    $brandName = config('mail.brand.name', 'Bama');
    $logoPath = config('mail.brand.logo_path', 'images/bama-logo.png');
    $logoUrl = $logoPath ? asset($logoPath) : null;
@endphp
<tr>
<td class="mail-brand-header">
<a href="{{ $url }}" class="mail-brand-mark">
@if($logoUrl)
<span class="mail-brand-logo-wrap">
<img src="{{ $logoUrl }}" class="mail-brand-logo" width="210" alt="{{ $brandName }}">
</span>
@else
{{ $brandName }}
@endif
</a>
</td>
</tr>
