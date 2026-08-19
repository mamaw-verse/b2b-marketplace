<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Company Onboarding') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Complete all steps to register your company on the marketplace.') }}</p>
        </div>

        <div class="p-6">
            {{-- Stepper / Progress indicator --}}
            <nav aria-label="Onboarding progress" class="mb-10">
                <ol class="flex items-center">
                    @for ($step = 1; $step <= 5; $step++)
                        <li class="flex items-center {{ $step < 5 ? 'flex-1' : '' }}">
                            {{-- Step circle --}}
                            <div class="flex flex-col items-center">
                                <div @class([
                                    'flex size-7 sm:size-10 items-center justify-center rounded-full text-xs sm:text-sm font-bold border-2 transition',
                                    'bg-[var(--hs-green-600)] border-[var(--hs-green-600)] text-white' => $step === $currentStep,
                                    'bg-[var(--hs-green-50)] border-[var(--hs-green-600)] text-[var(--hs-green-700)]' => $step < $currentStep,
                                    'bg-[var(--hs-neutral-100)] border-[var(--hs-neutral-200)] text-[var(--hs-neutral-500)]' => $step > $currentStep,
                                ])>
                                    @if ($step < $currentStep)
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    @else
                                        {{ $step }}
                                    @endif
                                </div>
                                <span @class([
                                    'mt-2 text-xs font-medium hidden sm:block',
                                    'text-[var(--hs-green-700)]' => $step <= $currentStep,
                                    'text-[var(--hs-neutral-500)]' => $step > $currentStep,
                                ])>
                                    @switch($step)
                                        @case(1) {{ __('Company') }} @break
                                        @case(2) {{ __('Operations') }} @break
                                        @case(3) {{ __('Halal Cert') }} @break
                                        @case(4) {{ __('Value Chain') }} @break
                                        @case(5) {{ __('Documents') }} @break
                                    @endswitch
                                </span>
                            </div>
                            {{-- Connecting line --}}
                            @if ($step < 5)
                                <div @class([
                                    'flex-1 h-0.5 mx-1 sm:mx-2',
                                    'bg-[var(--hs-green-600)]' => $step < $currentStep,
                                    'bg-[var(--hs-neutral-200)]' => $step >= $currentStep,
                                ])></div>
                            @endif
                        </li>
                    @endfor
                </ol>
            </nav>

            {{-- Flash messages --}}
            @if (session('message'))
                <div class="mb-6 rounded-[var(--hs-radius-card)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] p-4 text-sm text-[var(--hs-green-700)]">
                    {{ session('message') }}
                </div>
            @endif

            @if ($onboardingStatus === 'submitted')
                <div class="py-12 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-yellow-100">
                        <flux:icon name="clock" class="size-8 text-yellow-600" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Application Under Review') }}</h2>
                    <p class="mt-2 text-[var(--hs-neutral-600)] max-w-md mx-auto">
                        {{ __('Your onboarding application has been submitted and is currently being reviewed by our team. We will notify you once the review is complete.') }}
                    </p>
                </div>
            @elseif ($onboardingStatus === 'rejected')
                <div class="py-12 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-red-100">
                        <flux:icon name="x-circle" class="size-8 text-red-600" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Application Rejected') }}</h2>
                    <p class="mt-2 text-[var(--hs-neutral-600)] max-w-md mx-auto">
                        {{ __('Unfortunately, your application was rejected.') }}
                    </p>
                    @if ($adminNotes)
                        <div class="mt-6 mx-auto max-w-md p-4 bg-red-50 border border-red-100 rounded-lg text-left text-sm text-red-800">
                            <strong>{{ __('Reason:') }}</strong> {{ $adminNotes }}
                        </div>
                    @endif
                </div>
            @else
                @if ($onboardingStatus === 'requires_revision')
                    <div class="mb-6 rounded-[var(--hs-radius-card)] bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                        <strong>{{ __('Revision Required:') }}</strong> {{ $adminNotes }}
                    </div>
                @endif

                {{-- Step content --}}
                <div class="rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-100)] bg-[var(--hs-neutral-50)] p-6">
                    @switch($currentStep)
                        @case(1)
                            @include('livewire.company-hub.steps.step-1-company-profile')
                            @break
                        @case(2)
                            @include('livewire.company-hub.steps.step-2-operations')
                            @break
                        @case(3)
                            @include('livewire.company-hub.steps.step-3-halal-certification')
                            @break
                        @case(4)
                            @include('livewire.company-hub.steps.step-4-value-chain')
                            @break
                        @case(5)
                            @include('livewire.company-hub.steps.step-5-documents')
                            @break
                    @endswitch
                </div>

                {{-- Document submission error --}}
                @error('documents')
                    <div class="mt-4 rounded-[var(--hs-radius-card)] bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                {{-- Navigation buttons --}}
                <div class="mt-6 flex items-center justify-between">
                    <div>
                        @if ($currentStep > 1)
                            <flux:button wire:click="previousStep" variant="ghost">
                                {{ __('Previous') }}
                            </flux:button>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        <flux:button wire:click="saveCurrentStep" variant="subtle">
                            {{ __('Save Progress') }}
                        </flux:button>

                        @if ($currentStep < 5)
                            <flux:button wire:click="nextStep" variant="primary">
                                {{ __('Next') }}
                            </flux:button>
                        @else
                            <flux:button wire:click="submit" variant="primary">
                                {{ $onboardingStatus === 'requires_revision' ? __('Resubmit Application') : __('Submit Application') }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
