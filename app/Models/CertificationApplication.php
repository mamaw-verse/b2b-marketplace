<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CertificationApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $email
 * @property string|null $company_name
 * @property string|null $document_path
 * @property string $status
 * @property int|null $addressed_by
 * @property Carbon|null $addressed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class CertificationApplication extends Model
{
    /** @use HasFactory<CertificationApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'email',
        'company_name',
        'document_path',
        'status',
        'addressed_by',
        'addressed_at',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function addressedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'addressed_at' => 'datetime',
        ];
    }
}
