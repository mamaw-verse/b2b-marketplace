<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Mail\CertificationApplicationAcknowledgment;
use App\Models\CertificationApplication;
use App\Models\User;
use App\Notifications\CertificationInterestNotification;
use App\Rules\TurnstileRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.storefront')]
#[Title('Apply for Certificate')]
final class ApplyForCertificate extends Component
{
    use WithFileUploads;

    public string $email = '';

    public string $company_name = '';

    /**
     * Honeypot field. Hidden from human visitors via CSS; bots that fill it
     * are silently discarded without persisting an application.
     */
    public string $website = '';

    /** @var TemporaryUploadedFile|null */
    public $document = null;

    public string $turnstile_token = '';

    /** Set once the application is accepted, swaps the form for a confirmation. */
    public bool $submitted = false;

    /** Applicant email snapshot for the post-submit confirmation copy. */
    public string $submittedEmail = '';

    #[Computed]
    public function adminEmail(): string
    {
        $admin = User::role('admin')->first();

        return $admin?->email ?? (string) config('mail.admin_address', 'admin@example.com');
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $this->email = $user->email ?? '';

        $shop = $user->shop;
        $this->company_name = $shop->registered_business_name ?? $shop->storefront_name ?? '';
    }

    public function submit(): void
    {
        // Honeypot trap: pretend the submission succeeded without storing it.
        if ($this->website !== '') {
            $this->resetForm();
            $this->submitted = true;

            return;
        }

        $this->validate();

        $documentPath = null;

        if ($this->document) {
            $extension = mb_strtolower($this->document->getClientOriginalExtension() ?: 'bin');
            $documentPath = Storage::disk('s3')->putFileAs(
                'certification-applications/'.now()->format('Y/m/d'),
                $this->document,
                Str::uuid()->toString().'.'.$extension,
            );
        }

        $application = CertificationApplication::create([
            'shop_id' => null,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'document_path' => $documentPath,
            'status' => 'interested',
        ]);

        $this->notifyAdmins($application);

        Mail::to($this->email)->send(new CertificationApplicationAcknowledgment($application));

        $this->submittedEmail = $this->email;
        $this->resetForm();
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.storefront.apply-for-certificate');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'max:10240', 'mimes:doc,docx,pdf'],
            'turnstile_token' => ['required', 'string', new TurnstileRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'document.mimes' => 'Only DOC, DOCX and PDF files are accepted.',
            'document.max' => 'The Letter of Intent must not exceed 10 MB.',
        ];
    }

    private function notifyAdmins(CertificationApplication $application): void
    {
        $admins = User::role('admin')->get();

        $admins->each(function (User $admin) use ($application): void {
            $admin->notify(new CertificationInterestNotification($application->loadMissing('shop')));
        });

        // Fallback inbox when no admin-role user exists to receive the application.
        if ($admins->isEmpty()) {
            Notification::route('mail', config('mail.admin_address'))
                ->notify(new CertificationInterestNotification($application->loadMissing('shop')));
        }
    }

    private function resetForm(): void
    {
        $this->reset(['email', 'company_name', 'website', 'document', 'turnstile_token']);
        $this->resetErrorBag();
        $this->dispatch('turnstile-reset');
    }
}
