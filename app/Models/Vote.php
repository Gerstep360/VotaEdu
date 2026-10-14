<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    /**
     * Disable updated_at timestamp since votes are immutable once registered.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     * Note: user_id is intentionally excluded to guarantee secret ballot.
     *
     * @var list<string>
     */
    protected $fillable = [
        'election_id',
        'option_id',
    ];

    /**
     * Get the election for this vote.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * Get the option selected by this vote.
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'option_id');
    }
}
