<?php

declare(strict_types=1);

namespace App\Livewire\Storefront;

use App\Mail\SourcingRequestAcknowledgment;
use App\Models\SourcingRequest;
use App\Models\User;
use App\Notifications\SourcingRequestReceivedNotification;
use App\Rules\TurnstileRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class ProductSourcingForm extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $product_name = '';

    public string $product_description = '';

    /** @var TemporaryUploadedFile|null */
    public $reference_file = null;

    /**
     * Honeypot field. Hidden from human visitors via CSS; bots that fill it
     * are silently discarded without persisting a request.
     */
    public string $honeypot = '';

    public string $turnstile_token = '';

    public bool $submitted = false;

    public string $submittedEmail = '';

    public bool $fullPage = false;

    public function mount(?string $prefillProductName = null): void
    {
        $this->fullPage = (request()->route()?->getName() === 'sourcing-request.create');

        $user = auth()->user();

        if ($user !== null) {
            $this->name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            $this->email = $user->email ?? '';
        }

        if ($prefillProductName !== null) {
            $this->product_name = $prefillProductName;
        }
    }

    public function submit(): void
    {
        if ($this->honeypot !== '') {
            $this->resetForm();
            $this->submitted = true;

            return;
        }

        $this->validate();

        $referenceFilePath = null;

        if ($this->reference_file) {
            $extension = mb_strtolower($this->reference_file->getClientOriginalExtension() ?: 'bin');
            $referenceFilePath = Storage::disk('s3')->putFileAs(
                'sourcing-requests/'.now()->format('Y/m/d'),
                $this->reference_file,
                Str::uuid()->toString().'.'.$extension,
            );
        }

        $request = SourcingRequest::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'email' => $this->email,
            'product_name' => $this->product_name,
            'product_description' => $this->product_description !== '' ? $this->product_description : null,
            'reference_file_path' => $referenceFilePath,
        ]);

        $this->notifyAdmins($request);

        Mail::to($this->email)->send(new SourcingRequestAcknowledgment($request));

        $this->submittedEmail = $this->email;
        $this->resetForm();
        $this->submitted = true;
    }

    public function render(): View
    {
        $view = view('livewire.storefront.product-sourcing-form');

        if ($this->fullPage) {
            return $view->layout('layouts.storefront');
        }

        return $view;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_description' => ['nullable', 'string', 'max:5000'],
            'reference_file' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx'],
            'turnstile_token' => ['required', 'string', new TurnstileRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'reference_file.mimes' => 'Accepted file types: JPG, PNG, GIF, WebP, PDF, DOC, DOCX.',
            'reference_file.max' => 'The reference file must not exceed 10 MB.',
            'turnstile_token.required' => 'Please complete the security check.',
        ];
    }

    private function notifyAdmins(SourcingRequest $request): void
    {
        $admins = User::role('admin')->get();

        $admins->each(function (User $admin) use ($request): void {
            $admin->notify(new SourcingRequestReceivedNotification($request));
        });

        if ($admins->isEmpty()) {
            Notification::route('mail', config('mail.admin_address'))
                ->notify(new SourcingRequestReceivedNotification($request));
        }
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'email', 'product_name', 'product_description', 'reference_file', 'honeypot', 'turnstile_token']);
        $this->resetErrorBag();
        $this->dispatch('sourcing-turnstile-reset');
    }
}
