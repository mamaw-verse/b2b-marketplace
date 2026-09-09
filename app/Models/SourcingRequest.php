<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SourcingRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $email
 * @property string $product_name
 * @property string|null $product_description
 * @property string|null $reference_file_path
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SourcingRequest extends Model
{
    /** @use HasFactory<SourcingRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'product_name',
        'product_description',
        'reference_file_path',
        'status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
