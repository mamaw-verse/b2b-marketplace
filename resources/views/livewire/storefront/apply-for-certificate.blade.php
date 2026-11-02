<div class="bg-[var(--hs-neutral-50)]">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1.7fr_1fr] lg:items-start">

            {{-- ═══════════════════════════════════════════════════════
                 LEFT: Main application card
            ═══════════════════════════════════════════════════════ --}}
            <div class="rounded-2xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-0)] p-6 sm:p-8 shadow-[var(--hs-shadow-sm)]">

                @if (session('message'))
                    <div class="mb-6">
                        <flux:callout variant="success" icon="check-circle">{{ session('message') }}</flux:callout>
                    </div>
                @endif

                @if ($submitted)
                    <div class="flex flex-col items-center py-10 text-center">
                        <div class="flex size-14 items-center justify-center rounded-full bg-[var(--hs-green-50)] text-[var(--hs-green-700)]">
                            <flux:icon.check-circle class="size-8" />
                        </div>
                        <flux:heading size="xl" class="mt-5">Your application has been submitted.</flux:heading>
                        <flux:text class="mt-2 max-w-md">
                            We have received your Letter of Intent and will review it shortly.
                            @if ($submittedEmail)
                                A confirmation email is on its way to <span class="font-semibold text-[var(--hs-neutral-900)]">{{ $submittedEmail }}</span>.
                            @endif
                        </flux:text>
                    </div>
                @else
                    <form wire:submit="submit" novalidate>
                        <h1 class="text-2xl font-bold tracking-tight text-[var(--hs-neutral-900)]">Apply for Certificate</h1>
                        <p class="mt-1 text-sm text-[var(--hs-neutral-600)]">
                            To apply for a certificate, please download the Letter of Intent, fill it out, and upload it back here.
                        </p>

                        {{-- Step 1 — Download --}}
                        <div class="flex items-start gap-4 border-t border-[var(--hs-neutral-100)] py-5 first:border-t-0">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-sm font-semibold text-white">1</div>
                            <div class="flex-1">
                                <h3 class="text-[0.95rem] font-semibold text-[var(--hs-neutral-900)]">Download the Letter of Intent</h3>
                                <p class="mt-1 text-sm text-[var(--hs-neutral-600)] leading-relaxed">Click the button below to download the Letter of Intent.</p>
                                <a href="/docs/letter_of_intent_maslaha_halal_certification.docx"
                                   download
                                   class="mt-3 inline-flex items-center gap-2 rounded-md border border-[var(--hs-green-600)] bg-[var(--hs-neutral-0)] px-4 py-2.5 text-sm font-semibold text-[var(--hs-green-700)] transition hover:bg-[var(--hs-green-50)]">
                                    <flux:icon name="arrow-down-tray" class="size-4" />
                                    Download Letter of Intent
                                </a>
                            </div>
                        </div>

                        {{-- Step 2 — Complete and Sign --}}
                        <div class="flex items-start gap-4 border-t border-[var(--hs-neutral-100)] py-5">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-sm font-semibold text-white">2</div>
                            <div class="flex-1">
                                <h3 class="text-[0.95rem] font-semibold text-[var(--hs-neutral-900)]">Complete and Sign the Form</h3>
                                <p class="mt-1 text-sm text-[var(--hs-neutral-600)] leading-relaxed">Open the downloaded letter, fill in the required information, and sign it where indicated.</p>
                            </div>
                        </div>

                        {{-- Step 3 — Upload --}}
                        <div class="flex items-start gap-4 border-t border-[var(--hs-neutral-100)] pt-5">
                            <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[var(--hs-green-600)] text-sm font-semibold text-white">3</div>
                            <div class="flex-1">
                                <h3 class="text-[0.95rem] font-semibold text-[var(--hs-neutral-900)]">Upload the Completed Letter</h3>
                                <p class="mt-1 text-sm text-[var(--hs-neutral-600)] leading-relaxed">Once you've filled out and signed the letter, upload it below. We'll review your submission and process it as soon as possible.</p>

                                <x-hs-dropzone
                                    input-id="apply-certificate-document"
                                    wire-model="document"
                                    accept=".doc,.docx,.pdf,.DOC,.DOCX,.PDF"
                                    :multiple="false"
                                    hint="Accepted file types: DOC, DOCX, PDF • Max file size: 10MB"
                                    class="mt-4"
                                >
                                    @if ($document)
                                        <p class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[var(--hs-green-700)]">
                                            <flux:icon name="document-check" class="size-4" />
                                            {{ $document->getClientOriginalName() }}
                                        </p>
                                    @endif
                                    @error('document')
                                        <p class="mt-2 text-xs text-[var(--hs-danger-500)]">{{ $message }}</p>
                                    @enderror
                                </x-hs-dropzone>
                            </div>
                        </div>

                        {{-- Applicant details --}}
                        <div class="mt-7 rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 sm:p-6">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-[var(--hs-neutral-500)]">Your Details</h3>
                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label>Business Name</flux:label>
                                    <flux:input type="text" wire:model="company_name" placeholder="Acme Foods Inc." />
                                    <flux:error name="company_name" />
                                </flux:field>
                                <flux:field>
                                    <flux:label>Email Address</flux:label>
                                    <flux:input type="email" wire:model="email" placeholder="contact@example.com" />
                                    <flux:error name="email" />
                                </flux:field>
                            </div>

                            <div class="mt-4">
                                <flux:error name="human_check" />
                            </div>
                        </div>

                        {{-- Honeypot — hidden from humans, tempting for bots --}}
                        <div class="absolute left-[-9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                            <label for="apply-certificate-website">Leave this field empty</label>
                            <input type="text" id="apply-certificate-website" tabindex="-1" autocomplete="off" wire:model="website" />
                        </div>

                        <div class="mt-6">
                            <flux:button type="submit" variant="primary" class="!w-full" icon="paper-airplane" wire:loading.attr="disabled">
                                {{ __('Submit Application') }}
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 RIGHT: Important notes panel
            ═══════════════════════════════════════════════════════ --}}
            <aside class="order-first lg:order-last rounded-xl border border-[var(--hs-neutral-200)] bg-[var(--hs-neutral-50)] p-5 sm:p-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-[var(--hs-neutral-900)]">
                    <flux:icon name="information-circle" class="size-5 text-[var(--hs-green-700)]" />
                    Important Notes
                </h2>

                <ul class="mt-4 space-y-3.5">
                    @foreach ([
                        'The Letter of Intent is a required document for processing your certificate request.',
                        'Make sure to fill out all fields and sign the letter where indicated.',
                        'You can only upload one file per application.',
                        'Accepted file types: DOC, DOCX, PDF (Max size: 10MB).',
                    ] as $note)
                        <li class="flex items-start gap-2.5 text-[0.85rem] leading-relaxed text-[var(--hs-neutral-700)]">
                            <flux:icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-[var(--hs-green-700)]" />
                            {{ $note }}
                        </li>
                    @endforeach
                </ul>

                <hr class="my-5 border-[var(--hs-neutral-200)]">

                <div class="rounded-lg bg-[var(--hs-green-50)] p-4">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-[var(--hs-neutral-900)]">
                        <flux:icon name="lifebuoy" class="size-4 text-[var(--hs-green-700)]" />
                        Need Help?
                    </h3>
                    <p class="mt-2 text-[0.8rem] leading-relaxed text-[var(--hs-neutral-600)]">
                        If you have any questions or need assistance with the form, please email us at:
                    </p>
                    <a href="mailto:{{ $this->adminEmail }}" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-[var(--hs-green-700)] hover:text-[var(--hs-green-800)]">
                        <flux:icon name="envelope" class="size-4" />
                        {{ $this->adminEmail }}
                    </a>
                </div>
            </aside>
        </div>
    </div>
</div>