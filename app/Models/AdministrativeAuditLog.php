<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AdministrativeAuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to quickly record an administrative action.
     */
    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, array|string|null $details = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => is_array($details) ? $details : ($details ? ['message' => $details] : null),
            'ip_address' => Request::ip(),
        ]);
    }
}
