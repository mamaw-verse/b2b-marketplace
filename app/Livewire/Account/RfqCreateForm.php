<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Events\RfqCreated;
use App\Models\Rfq;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.storefront')]
#[Title('Request Quote')]
final class RfqCreateForm extends Component
{
    use WithFileUploads;

    #[Url]
    public ?int $shop_id = null;

    #[Url]
    public string $title = '';

    public string $description = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $attachment = null;

    public function mount(): void
    {
        // Buyer profile is no longer required before rendering the form.
        // The submit() action still validates the profile exists before saving.
    }

    public function submit(): void
    {
        $this->validate();

        $user = Auth::user();
        $buyerProfile = $user->buyerProfile;

        if (! $buyerProfile) {
            $this->addError('buyer_profile', 'Please complete your buyer profile before sending an RFQ.');

            return;
        }

        $shop = Shop::where('id', $this->shop_id)
            ->where('status', 'active')
            ->first();

        if (! $shop) {
            $this->addError('shop_id', 'The selected shop is not available.');

            return;
        }

        // Store single attachment as a media record if provided
        $mediaIds = [];
        if ($this->attachment) {
            $media = app(\App\Services\MediaUploadService::class)->store(
                $this->attachment,
                $user->id,
                'rfq_attachment',
            );
            $mediaIds[] = $media->id;
        }

        $rfq = DB::transaction(function () use ($user, $buyerProfile, $shop, $mediaIds) {
            $rfq = Rfq::create([
                'user_id' => $user->id,
                'title' => $this->title,
                'description' => $this->description ?: null,
                'status' => 'open',
                'buyer_snapshot' => $buyerProfile->toSnapshot(),
                'last_activity_at' => now(),
            ]);

            $rfq->recipients()->create([
                'shop_id' => $shop->id,
                'status' => 'received',
                'last_activity_at' => now(),
            ]);

            if (! empty($mediaIds)) {
                $rfq->attachments()->attach($mediaIds);
            }

            return $rfq;
        });

        RfqCreated::dispatch($rfq);

        \Flux\Flux::toast('RFQ submitted! You can track it under My RFQs.', variant: 'success');

        $this->redirect(route('account.rfqs.show', $rfq), navigate: true);
    }

    public function render()
    {
        $shop = $this->shop_id ? Shop::find($this->shop_id) : null;

        return view('livewire.account.rfq-create-form', [
            'shop' => $shop,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                // Explicit allow-list: PDF, Word, and common image formats only.
                // text/plain and other generic types are rejected.
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'attachment.mimes' => 'Only PDF, Word, and image files are accepted.',
        ];
    }
}
