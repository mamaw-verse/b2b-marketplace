<div>
    <div class="bg-[var(--hs-neutral-0)] rounded-[var(--hs-radius-card)] border border-[var(--hs-neutral-200)] shadow-[var(--hs-shadow-sm)]">
        {{-- Card Header --}}
        <div class="px-6 py-5 border-b border-[var(--hs-neutral-100)]">
            <h1 class="text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Company Onboarding') }}</h1>
            <p class="mt-1 text-sm text-[var(--hs-neutral-500)]">{{ __('Complete all steps to register your company on the marketplace.') }}</p>
        </div>

        <div class="p-6">
            {{-- Stepper / Progress indicator --}}
            <nav aria-label="Onboarding progress" class="mb-4">
                <ol class="flex items-center">
                    @for ($step = 1; $step <= 5; $step++)
                        @php
                            $stepLabel = match ($step) {
                                1 => __('Company'),
                                2 => __('Operations'),
                                3 => __('Halal Cert'),
                                4 => __('Value Chain'),
                                5 => __('Documents'),
                            };
                        @endphp
                        <li class="flex items-center {{ $step < 5 ? 'flex-1' : '' }}" @if ($step === $currentStep) aria-current="step" @endif>
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
                                    {{ $stepLabel }}
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
                {{-- Mobile-only caption, since labels hide below sm: --}}
                <p class="mt-2 text-xs font-medium text-[var(--hs-green-700)] sm:hidden">
                    {{ __('Step :current of 5 — :label', ['current' => $currentStep, 'label' => match ($currentStep) {
                        1 => __('Company'), 2 => __('Operations'), 3 => __('Halal Cert'),
                        4 => __('Value Chain'), 5 => __('Documents'),
                    }]) }}
                </p>
            </nav>

            {{-- Flash messages --}}
            @if (session('message'))
                <div class="mb-6 rounded-[var(--hs-radius-card)] bg-[var(--hs-green-50)] border border-[var(--hs-green-200)] p-4 text-sm text-[var(--hs-green-700)]">
                    {{ session('message') }}
                </div>
            @endif

            @if ($onboardingStatus === 'submitted')
                <div class="py-12 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-[var(--hs-warning-50)]">
                        <flux:icon name="clock" class="size-8 text-[var(--hs-warning-700)]" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Application Under Review') }}</h2>
                    <p class="mt-2 text-[var(--hs-neutral-600)] max-w-md mx-auto">
                        {{ __('Your onboarding application has been submitted and is currently being reviewed by our team. We will notify you once the review is complete.') }}
                    </p>
                </div>
            @elseif ($onboardingStatus === 'rejected')
                <div class="py-12 text-center">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-[var(--hs-danger-50)]">
                        <flux:icon name="x-circle" class="size-8 text-[var(--hs-danger-700)]" />
                    </div>
                    <h2 class="mt-4 text-xl font-semibold text-[var(--hs-neutral-900)]">{{ __('Application Rejected') }}</h2>
                    <p class="mt-2 text-[var(--hs-neutral-600)] max-w-md mx-auto">
                        {{ __('Unfortunately, your application was rejected.') }}
                    </p>
                    @if ($adminNotes)
                        <div class="mt-6 mx-auto max-w-md p-4 bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] rounded-lg text-left text-sm text-[var(--hs-danger-700)]">
                            <strong>{{ __('Reason:') }}</strong> {{ $adminNotes }}
                        </div>
                    @endif

                    <div class="mt-6">
                        <flux:button wire:click="reapply" variant="primary">
                            {{ __('Re-apply') }}
                        </flux:button>
                    </div>
                </div>
            @else
                @if ($onboardingStatus === 'requires_revision')
                    <div
                        role="alert"
                        aria-live="polite"
                        class="mb-6 flex gap-4 overflow-hidden rounded-[var(--hs-radius-card)] border-2 border-[var(--hs-warning-200)] bg-[var(--hs-warning-50)] p-5 shadow-[var(--hs-shadow-sm)]"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[var(--hs-warning-100)]">
                            <flux:icon name="exclamation-triangle" class="size-6 text-[var(--hs-warning-700)]" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-base font-semibold text-[var(--hs-warning-700)]">
                                {{ __('Revision Required') }}
                            </h2>
                            <p class="mt-0.5 text-sm text-[var(--hs-neutral-700)]">
                                {{ __('An administrator has asked you to update your application before it can be reviewed again.') }}
                            </p>
                            @if ($adminNotes)
                                <div class="mt-3 rounded-[var(--hs-radius-sm)] border border-[var(--hs-warning-200)] bg-[var(--hs-neutral-0)] p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">{{ __('Reviewer notes') }}</p>
                                    <p class="mt-1 text-sm text-[var(--hs-neutral-900)]">{{ $adminNotes }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Consolidated validation error summary — single source of truth,
                     with a jump-to-step action when the error lives on a step
                     that isn't currently visible. --}}
                @if ($errors->any())
                    <div role="alert" aria-live="polite" class="mb-6 rounded-[var(--hs-radius-card)] bg-[var(--hs-danger-50)] border border-[var(--hs-danger-200)] p-4">
                        <div class="flex gap-3">
                            <flux:icon name="exclamation-triangle" class="size-5 shrink-0 text-[var(--hs-danger-700)]" />
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-[var(--hs-danger-700)]">{{ __('Please fix the following before continuing:') }}</p>
                                <ul class="mt-2 space-y-2 text-sm text-[var(--hs-danger-700)]">
                                    @foreach ($errors->keys() as $errorKey)
                                        @php $errorStep = $this->stepForErrorKey($errorKey); @endphp
                                        <li class="flex items-center justify-between gap-3">
                                            <span class="min-w-0">
                                                <span class="font-medium">{{ $this->fieldLabels[$errorKey] ?? \Illuminate\Support\Str::headline(\Illuminate\Support\Str::afterLast($errorKey, '.')) }}:</span>
                                                {{ $errors->first($errorKey) }}
                                            </span>
                                            @if ($errorStep && $errorStep !== $currentStep)
                                                <flux:button size="sm" variant="ghost" wire:click="goToStep({{ $errorStep }})" class="shrink-0 whitespace-nowrap">
                                                    {{ __('Go to Step :n →', ['n' => $errorStep]) }}
                                                </flux:button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
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

                {{-- Navigation buttons --}}
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center justify-between gap-3 sm:justify-start">
                        @if ($currentStep > 1)
                            <flux:button
                                wire:click="previousStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,saveCurrentStep,submit"
                                variant="ghost"
                            >
                                {{ __('Previous') }}
                            </flux:button>
                        @endif

                        <flux:button
                            wire:click="saveCurrentStep"
                            wire:loading.attr="disabled"
                            wire:target="previousStep,nextStep,saveCurrentStep,submit"
                            variant="subtle"
                            class="{{ $currentStep === 1 ? 'ml-auto sm:ml-0' : '' }}"
                        >
                            <span wire:loading.remove wire:target="saveCurrentStep">{{ __('Save Progress') }}</span>
                            <span wire:loading wire:target="saveCurrentStep">{{ __('Saving…') }}</span>
                        </flux:button>
                    </div>

                    <div>
                        @if ($currentStep < 5)
                            <flux:button
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,saveCurrentStep,submit"
                                variant="primary"
                                class="w-full sm:w-auto"
                            >
                                {{ __('Next') }}
                            </flux:button>
                        @else
                            <flux:button
                                wire:click="submit"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,saveCurrentStep,submit"
                                variant="primary"
                                class="w-full sm:w-auto"
                            >
                                <span wire:loading.remove wire:target="submit">
                                    {{ $onboardingStatus === 'requires_revision' ? __('Resubmit Application') : __('Submit Application') }}
                                </span>
                                <span wire:loading wire:target="submit">{{ __('Submitting…') }}</span>
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>