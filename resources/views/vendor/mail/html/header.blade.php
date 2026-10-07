@props(['url'])
@php($branding = \App\Models\BrandingSetting::current())
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $branding->rasterLogoUrl() }}" class="logo" alt="{{ config('app.name') }}" style="max-height: 75px; max-width: 220px; width: auto;">
</a>
</td>
</tr>
