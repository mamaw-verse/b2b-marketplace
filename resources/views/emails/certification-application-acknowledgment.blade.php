<x-mail::message>
# {{ __('Application received for Halal Certification') }}

{{ __('Dear **:companyName**,', ['companyName' => $application->company_name]) }}

{{ __('We have received your Letter of Intent for Halal Certification. Our team will review the document and contact you at **:email** with the next steps.', ['email' => $application->email]) }}

{{ __('If you have any questions in the meantime, feel free to reply to this email.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>