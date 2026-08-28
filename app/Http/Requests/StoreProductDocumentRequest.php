<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via Gate in controller
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $allowedMimes = implode(',', MediaUploadService::allowedExtensions());

        return [
            'document' => ['required', 'file', 'max:20480', "mimes:{$allowedMimes}"],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
