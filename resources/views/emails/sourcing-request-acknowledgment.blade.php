<x-mail::message>
# Sourcing Request Received

Dear **{{ $sourcingRequest->name }}**,

We have received your product sourcing request for **{{ $sourcingRequest->product_name }}**.

Our sourcing team will review your requirements and contact you at **{{ $sourcingRequest->email }}** with suitable options based on your specifications.

If you have any additional details to share, feel free to reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
