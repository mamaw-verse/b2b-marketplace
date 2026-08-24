<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertPayoutAccountRequest extends FormRequest
{
    /**
     * @return array<int, string>
     */
    public static function philippineBanks(): array
    {
        return [
            'BDO',
            'BPI',
            'Metrobank',
            'Landbank',
            'PNB',
            'UnionBank',
            'RCBC',
            'Security Bank',
            'China Banking Corporation',
            'EastWest Bank',
            'PSBank',
            'Robinsons Bank',
            'UCPB',
            'DBP',
            'AUB',
            'PBCom',
            'Chinabank Savings',
            'Bank of Commerce',
            'Maybank Philippines',
            'CTBC Bank Philippines',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();

        return $user->shop !== null && $user->shop->status === 'active';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:150', Rule::in(self::philippineBanks())],
            'bank_code' => ['required', 'string', 'max:30'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'branch' => ['nullable', 'string', 'max:150'],
        ];
    }
}
