<x-mail::message>
# Application received for Halal Certification

Dear **{{ $application->company_name }}**,

We have received your Letter of Intent for Halal Certification. Our team will review the document and contact you at **{{ $application->email }}** with the next steps.

If you have any questions in the meantime, feel free to reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>