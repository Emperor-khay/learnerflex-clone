@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Learnerflex')
<img src={{ asset('mail-logo.png') }} class="logo" alt="Learnerflex Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
