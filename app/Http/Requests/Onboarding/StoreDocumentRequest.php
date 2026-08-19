<?php

declare(strict_types=1);

namespace App\Http\Requests\Onboarding;

use App\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxSize = MediaUploadService::maxFileSize() / 1024; // KB
        $mimes = implode(',', MediaUploadService::allowedExtensions());

        return [
            'file' => ['required', 'file', "max:{$maxSize}", "mimes:{$mimes}"],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
