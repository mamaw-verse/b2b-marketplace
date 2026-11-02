<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.portal', ['portal' => 'account'])] #[Title('Profile settings')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules, WithFileUploads;

    // ── Profile ──────────────────────────────────────────────
    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public $photo = null;

    public bool $removePhoto = false;

    // ── Security (password) ──────────────────────────────────
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // ── Security (2FA / passkeys) ────────────────────────────
    public bool $canManageTwoFactor = false;

    public bool $twoFactorEnabled = false;

    public bool $requiresConfirmation = false;

    #[Locked]
    public bool $canManagePasskeys = false;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $user = Auth::user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->photo) {
            $this->deleteStoredPhoto($user);

            $user->photo_path = $this->photo->store('profile-photos', 's3');
        } elseif ($this->removePhoto) {
            $this->deleteStoredPhoto($user);

            $user->photo_path = null;
        }

        $user->save();

        $this->reset('photo', 'removePhoto');

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Clear the pending upload and flag the stored photo for removal on save.
     */
    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
    }

    /**
     * Delete the user's stored profile photo file, if any.
     */
    private function deleteStoredPhoto($user): void
    {
        if (! $user->photo_path || str_starts_with($user->photo_path, 'http')) {
            return;
        }

        // Current home is the s3 disk; the public disk is checked too so
        // photos stored before the s3 move don't orphan.
        foreach (['s3', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($user->photo_path)) {
                Storage::disk($disk)->delete($user->photo_path);
            }
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        if (! $this->requireRecentAuthentication()) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->requireRecentAuthentication() || ! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        if (! $this->requireRecentAuthentication()) {
            return;
        }

        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    #[Computed]
    public function hasRecentAuthentication(): bool
    {
        $confirmedAt = (int) session()->get('auth.password_confirmed_at', 0);

        return now()->timestamp - $confirmedAt < (int) config('auth.password_timeout', 10800);
    }

    private function requireRecentAuthentication(): bool
    {
        if ($this->hasRecentAuthentication) {
            return true;
        }

        session()->put('url.intended', route('profile.edit').'#security');
        $this->redirect(route('password.confirm'), navigate: true);

        return false;
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
}; ?>

<section class="w-full">
    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <div class="w-full space-y-6">
        {{-- ── Profile ─────────────────────────────────────────── --}}
        <div id="profile">
        <x-pages::settings.layout width="max-w-none" :heading="__('Profile')" :subheading="__('Update your name, photo, phone number and email address')">
            <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
                {{-- Photo --}}
                <div>
                    <label class="block text-sm font-medium text-[var(--hs-neutral-700)] mb-1.5">{{ __('Profile photo') }}</label>

                    @php
                        $existingPhoto = (! $removePhoto && auth()->user()->photo_path)
                            ? auth()->user()->photo_url
                            : null;
                        $pendingPhoto = $photo instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                            ? $photo->temporaryUrl()
                            : null;
                        $previewUrl = $pendingPhoto ?? $existingPhoto;
                    @endphp

                    @if ($previewUrl)
                        {{-- Preview state: show photo with remove/replace button --}}
                        <div class="relative inline-block group">
                            <img
                                src="{{ $previewUrl }}"
                                alt="{{ __('Profile photo preview') }}"
                                class="size-28 rounded-full object-cover border border-[var(--hs-neutral-200)]"
                            />
                            <button
                                type="button"
                                wire:click="clearPhoto"
                                aria-label="{{ __('Remove photo') }}"
                                class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-full bg-[var(--hs-danger-500)] text-white opacity-0 transition group-hover:opacity-100 hover:bg-[var(--hs-danger-700)]"
                            >
                                <flux:icon name="x-mark" variant="mini" class="size-3.5" />
                            </button>
                            <p class="mt-1.5 text-xs text-[var(--hs-neutral-500)]">
                                {{ __('Hover to remove and replace.') }}
                            </p>
                        </div>
                    @else
                        {{-- Dropzone state --}}
                        <x-hs-dropzone
                            input-id="profile-photo-input"
                            wire-model="photo"
                            accept="image/*"
                            :multiple="false"
                            hint="{{ __('JPEG, PNG, WebP — up to 5MB') }}"
                            loading-target="photo"
                        >
                            @error('photo') <p class="mt-2 text-sm text-[var(--hs-danger-700)]">{{ $message }}</p> @enderror
                        </x-hs-dropzone>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input wire:model="first_name" :label="__('First name')" type="text" required autofocus autocomplete="given-name" />
                    <flux:input wire:model="last_name" :label="__('Last name')" type="text" required autocomplete="family-name" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                        @if ($this->hasUnverifiedEmail)
                            <div>
                                <flux:text class="mt-4">
                                    {{ __('Your email address is unverified.') }}

                                    <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                        {{ __('Click here to re-send the verification email.') }}
                                    </flux:link>
                                </flux:text>

                                @if (session('status') === 'verification-link-sent')
                                    <flux:text class="mt-2 font-medium text-[var(--hs-green-700)]">
                                        {{ __('A new verification link has been sent to your email address.') }}
                                    </flux:text>
                                @endif
                            </div>
                        @endif
                    </div>

                    <flux:input wire:model="phone" :label="__('Phone number')" type="tel" autocomplete="tel" placeholder="+63 917 123 4567" />
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-end">
                        <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                            {{ __('Save') }}
                        </flux:button>
                    </div>

                </div>
            </form>
        </x-pages::settings.layout>
        </div>

        {{-- ── Appearance ──────────────────────────────────────── --}}
        <div id="appearance">
        <x-pages::settings.layout width="max-w-none" :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </x-pages::settings.layout>
        </div>

        {{-- ── Security ────────────────────────────────────────── --}}
        <div id="security">
        <x-pages::settings.layout width="max-w-none" :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
            <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <flux:input
                    wire:model="current_password"
                    :label="__('Current password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />
                <flux:input
                    wire:model="password"
                    :label="__('New password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                    viewable
                />
                </div>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit" data-test="update-password-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>

            @if (! $this->hasRecentAuthentication)
                <flux:callout class="mt-12" variant="warning" icon="lock-closed" heading="{{ __('Confirm your identity') }}">
                    <flux:text>
                        {{ __('Confirm your identity before managing two-factor authentication, passkeys, or recovery codes.') }}
                    </flux:text>
                    <div class="mt-4">
                        <flux:button :href="route('password.confirm')" variant="primary">
                            {{ __('Confirm identity') }}
                        </flux:button>
                    </div>
                </flux:callout>
            @else
            @if ($canManageTwoFactor)
                <section class="mt-12">
                    <flux:heading>{{ __('Two-factor authentication') }}</flux:heading>
                    <flux:subheading>{{ __('Manage your two-factor authentication settings') }}</flux:subheading>

                    <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                        @if ($twoFactorEnabled)
                            <div class="space-y-4">
                                <flux:text>
                                    {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                                </flux:text>

                                <div class="flex justify-start">
                                    <flux:button
                                        variant="danger"
                                        wire:click="disable"
                                    >
                                        {{ __('Disable 2FA') }}
                                    </flux:button>
                                </div>

                                <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                            </div>
                        @else
                            <div class="space-y-4">
                                <flux:text variant="subtle">
                                    {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                                </flux:text>

                                <flux:modal.trigger name="two-factor-setup-modal">
                                    <flux:button
                                        variant="primary"
                                        wire:click="$dispatch('start-two-factor-setup')"
                                    >
                                        {{ __('Enable 2FA') }}
                                    </flux:button>
                                </flux:modal.trigger>

                                <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if ($canManagePasskeys)
                <section class="mt-12">
                    <flux:heading>{{ __('Passkeys') }}</flux:heading>
                    <flux:subheading>{{ __('Manage your passkeys for passwordless sign-in') }}</flux:subheading>

                    <div class="mt-6 flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                        <div class="border rounded-lg border-[var(--hs-neutral-200)] overflow-hidden">
                            @forelse ($passkeys as $passkey)
                                <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-[var(--hs-neutral-200)]' : '' }}">
                                    <div class="flex items-center gap-4">
                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[var(--hs-neutral-100)]">
                                            <flux:icon.key class="size-5 text-[var(--hs-neutral-500)]" />
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2.5">
                                                <p class="font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                                @if ($passkey['authenticator'])
                                                    <flux:badge size="sm">{{ $passkey['authenticator'] }}</flux:badge>
                                                @endif
                                            </div>
                                            <p class="text-[var(--hs-neutral-500)] text-xs">
                                                {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                                @if ($passkey['last_used_at_diff'])
                                                    <span class="opacity-50 mx-1">/</span>
                                                    {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        icon:variant="outline"
                                        wire:click="confirmDelete({{ $passkey['id'] }})"
                                        class="text-red-500 hover:text-red-600 hover:bg-[var(--hs-danger-50)]"
                                    />
                                </div>
                            @empty
                                <div class="p-8 text-center">
                                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-[var(--hs-neutral-100)]">
                                        <flux:icon.key class="size-7 text-[var(--hs-neutral-400)]" />
                                    </div>
                                    <p class="font-medium">{{ __('No passkeys yet') }}</p>
                                    <flux:text class="mt-1">{{ __('Add a passkey to sign in without a password') }}</flux:text>
                                </div>
                            @endforelse
                        </div>

                        <x-passkey-registration />
                    </div>
                </section>
            @endif
            @endif
        </x-pages::settings.layout>
        </div>
    </div>

    <flux:modal
        name="delete-passkey-modal"
        class="max-w-md md:min-w-md"
        @close="closeDeleteModal"
        wire:model="showDeleteModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove passkey') }}</flux:heading>
                <flux:text>
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </flux:text>
            </div>

            <div class="flex gap-3 justify-end">
                <flux:button
                    variant="outline"
                    wire:click="closeDeleteModal"
                >
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button
                    variant="danger"
                    wire:click="deletePasskey"
                >
                    {{ __('Remove passkey') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
