<div class="space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __('Document Upload Checklist') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Upload all required documents below. Accepted formats: PDF, JPG, PNG (max 20MB each).') }}</flux:text>
        </div>
        {{-- Refresh button so users can check if pending scans completed --}}
        <flux:button wire:click="refreshDocumentStatuses" variant="ghost" size="sm" icon="arrow-path">
            {{ __('Refresh Status') }}
        </flux:button>
    </div>

    @foreach (self::REQUIRED_DOCUMENTS as $type => $label)
        @php $doc = $uploadedDocuments[$type] ?? null; @endphp
        <flux:card class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <flux:heading size="sm">{{ __($label) }}</flux:heading>

                        @if ($doc)
                            @switch($doc['scan_status'])
                                @case('clean')
                                    <flux:badge color="green" size="sm">✓ {{ __('Verified') }}</flux:badge>
                                    @break
                                @case('pending')
                                    <flux:badge color="yellow" size="sm">{{ __('Scanning…') }}</flux:badge>
                                    @break
                                @case('infected')
                                    <flux:badge color="red" size="sm">✗ {{ __('Failed scan') }}</flux:badge>
                                    @break
                                @default
                                    <flux:badge size="sm">{{ __('Uploaded') }}</flux:badge>
                            @endswitch
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('Required') }}</flux:badge>
                        @endif
                    </div>

                    @if ($doc)
                        <flux:text class="mt-1 text-xs truncate">{{ $doc['filename'] }}</flux:text>

                        @if ($doc['scan_status'] === 'pending')
                            <flux:text class="mt-1 text-xs text-yellow-600">
                                {{ __('Security scan in progress. Click "Refresh Status" to check, then re-upload if it stays here.') }}
                            </flux:text>
                        @elseif ($doc['scan_status'] === 'infected')
                            <flux:text class="mt-1 text-xs text-red-600">
                                {{ __('This file failed the security scan. Please upload a different file.') }}
                            </flux:text>
                        @endif
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if (! $doc || $doc['scan_status'] === 'infected')
                        <flux:input
                            wire:model="documentUploads.{{ $type }}"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                        />
                        <flux:button
                            wire:click="uploadDocument('{{ $type }}')"
                            variant="primary"
                            size="sm"
                        >
                            {{ __('Upload') }}
                        </flux:button>
                    @elseif ($doc['scan_status'] === 'clean')
                        <flux:icon name="check-circle" class="size-5 text-green-500" />
                    @elseif ($doc['scan_status'] === 'pending')
                        <flux:icon name="clock" class="size-5 text-yellow-500" />
                    @endif
                </div>
            </div>

            @error("documentUploads.{$type}")
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </flux:card>
    @endforeach
</div>
