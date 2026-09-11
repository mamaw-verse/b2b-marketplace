<x-mail::message>
# {{ __('Sourcing Request Received') }}

{{ __('Dear **:name**,', ['name' => $sourcingRequest->name]) }}

{{ __('We have received your product sourcing request for **:productName**.', ['productName' => $sourcingRequest->product_name]) }}

{{ __('Our sourcing team will review your requirements and contact you at **:email** with suitable options based on your specifications.', ['email' => $sourcingRequest->email]) }}

{{ __('If you have any additional details to share, feel free to reply to this email.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>