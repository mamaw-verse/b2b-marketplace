<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Events\RfqCreated;
use App\Models\Product;
use App\Models\Rfq;
use App\Models\Shop;
use App\Services\MediaUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.storefront')]
#[Title('Request Quote')]
final class RfqCreateForm extends Component
{
    use WithFileUploads;

    // -------------------------------------------------------------------------
    // URL-bound query params
    // -------------------------------------------------------------------------

    #[Url]
    public ?int $shop_id = null;

    /**
     * The product the buyer came from. Used as visual reference on the form and
     * persisted as product_id on the created RFQ so it can be shown on detail pages.
     */
    #[Url]
    public ?int $product_id = null;

    #[Url]
    public string $title = '';

    // -------------------------------------------------------------------------
    // Step state: 1 = profile setup, 2 = RFQ form
    // -------------------------------------------------------------------------

    public int $step = 2;

    // -------------------------------------------------------------------------
    // Step 1 — Buyer profile fields
    // -------------------------------------------------------------------------

    public string $business_name = '';

    public string $billing_address_1 = '';

    public string $billing_address_2 = '';

    public string $billing_city = '';

    public string $billing_province = '';

    public string $billing_zip_code = '';

    public string $billing_country_code = 'PH';

    public string $shipping_address_1 = '';

    public string $shipping_address_2 = '';

    public string $shipping_city = '';

    public string $shipping_province = '';

    public string $shipping_zip_code = '';

    public string $shipping_country_code = 'PH';

    public string $contact_phone = '';

    public string $contact_email = '';

    public bool $useShippingSameAsBilling = true;

    // -------------------------------------------------------------------------
    // Step 2 — RFQ fields
    // -------------------------------------------------------------------------

    public string $description = '';

    /** @var TemporaryUploadedFile|null */
    public $attachment = null;

    /**
     * Set once the RFQ is persisted. Drives the in-place success state instead of
     * redirecting, so the buyer keeps their place in the storefront.
     */
    #[Locked]
    public ?int $submittedRfqId = null;

    #[Locked]
    public string $submittedTitle = '';

    public function mount(): void
    {
        $profile = Auth::user()->buyerProfile;

        if (! $profile) {
            $this->step = 1;

            return;
        }

        // Profile exists — pre-populate in case the buyer navigates back.
        $this->step = 2;
    }

    // -------------------------------------------------------------------------
    // Step 1 action
    // -------------------------------------------------------------------------

    public function saveProfile(): void
    {
        $this->validate($this->profileRules());

        $data = [
            'business_name' => $this->business_name,
            'billing_address_1' => $this->billing_address_1 ?: null,
            'billing_address_2' => $this->billing_address_2 ?: null,
            'billing_city' => $this->billing_city ?: null,
            'billing_province' => $this->billing_province ?: null,
            'billing_zip_code' => $this->billing_zip_code ?: null,
            'billing_country_code' => $this->billing_country_code ?: 'PH',
            'shipping_address_1' => $this->useShippingSameAsBilling ? ($this->billing_address_1 ?: null) : ($this->shipping_address_1 ?: null),
            'shipping_address_2' => $this->useShippingSameAsBilling ? ($this->billing_address_2 ?: null) : ($this->shipping_address_2 ?: null),
            'shipping_city' => $this->useShippingSameAsBilling ? ($this->billing_city ?: null) : ($this->shipping_city ?: null),
            'shipping_province' => $this->useShippingSameAsBilling ? ($this->billing_province ?: null) : ($this->shipping_province ?: null),
            'shipping_zip_code' => $this->useShippingSameAsBilling ? ($this->billing_zip_code ?: null) : ($this->shipping_zip_code ?: null),
            'shipping_country_code' => $this->useShippingSameAsBilling ? ($this->billing_country_code ?: 'PH') : ($this->shipping_country_code ?: 'PH'),
            'contact_phone' => $this->contact_phone ?: null,
            'contact_email' => $this->contact_email ?: null,
        ];

        Auth::user()->buyerProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            $data,
        );

        $this->step = 2;
    }

    // -------------------------------------------------------------------------
    // Step 2 action
    // -------------------------------------------------------------------------

    public function submit(): void
    {
        // The component is no longer torn down by a redirect, so refuse a second save.
        if ($this->submittedRfqId !== null) {
            return;
        }

        $this->validate($this->rfqRules());

        $user = Auth::user();
        $buyerProfile = $user->buyerProfile;

        if (! $buyerProfile) {
            // This shouldn't happen in normal flow, but guard against stale state.
            $this->step = 1;

            return;
        }

        $shop = Shop::where('id', $this->shop_id)
            ->where('status', 'active')
            ->first();

        if (! $shop) {
            $this->addError('shop_id', 'The selected shop is not available.');

            return;
        }

        // Persist the product the RFQ is asking a quotation for, so it can be
        // shown on the buyer and admin RFQ detail pages. Only link products that
        // are published and actually belong to the targeted shop.
        $productId = $this->product_id
            ? Product::published()
                ->where('shop_id', $shop->id)
                ->value('id')
            : null;

        // Store single attachment as a media record if provided
        $mediaIds = [];
        if ($this->attachment) {
            $media = app(MediaUploadService::class)->store(
                $this->attachment,
                $user->id,
                'rfq_attachment',
            );
            $mediaIds[] = $media->id;
        }

        $rfq = DB::transaction(function () use ($user, $buyerProfile, $shop, $productId, $mediaIds) {
            $rfq = Rfq::create([
                'user_id' => $user->id,
                'product_id' => $productId,
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

        // Stay on the page and swap the form for an in-place confirmation.
        $this->submittedRfqId = $rfq->id;
        $this->submittedTitle = $rfq->title;
        $this->reset(['description', 'attachment']);
    }

    // -------------------------------------------------------------------------
    // File upload helpers
    // -------------------------------------------------------------------------

    /**
     * Discard the staged temporary upload so the buyer can pick a different file.
     */
    public function clearAttachment(): void
    {
        $this->attachment = null;
        $this->resetErrorBag('attachment');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        $shop = $this->shop_id
            ? Shop::with('certificates')->where('status', 'active')->find($this->shop_id)
            : null;

        // Only surface the reference product when it is published and actually
        // belongs to the shop this RFQ is addressed to.
        $product = $shop && $this->product_id
            ? Product::published()
                ->where('shop_id', $shop->id)
                ->find($this->product_id)
            : null;

        return view('livewire.account.rfq-create-form', [
            'shop' => $shop,
            'product' => $product,
        ]);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        // Called by Livewire's real-time validation — delegate to the active step's rules.
        return $this->step === 1
            ? $this->profileRules()
            : $this->rfqRules();
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

    /**
     * @return array<string, array<int, string>>
     */
    private function profileRules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'billing_address_1' => ['nullable', 'string', 'max:255'],
            'billing_address_2' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_province' => ['nullable', 'string', 'max:120'],
            'billing_zip_code' => ['nullable', 'string', 'max:20'],
            'billing_country_code' => ['nullable', 'string', 'size:2'],
            'shipping_address_1' => ['nullable', 'string', 'max:255'],
            'shipping_address_2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_province' => ['nullable', 'string', 'max:120'],
            'shipping_zip_code' => ['nullable', 'string', 'max:20'],
            'shipping_country_code' => ['nullable', 'string', 'size:2'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rfqRules(): array
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
}
