{{-- <x-mail::message>
# Proudct Purchase Successful, Register with link

<a href="https://learnerflex.com/auth/signup?aff_id={{ $aff_id }}">https://website.com?aff_id={{ $aff_id }}</a>
<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message> --}}

@component('mail::message')
# Product Purchase Successful

Click the link below to register:

<a href="{{ $url }}">https://website.com?aff_id={{ $aff_id }}</a>

@component('mail::button', ['url' => $url])
Register Now
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

