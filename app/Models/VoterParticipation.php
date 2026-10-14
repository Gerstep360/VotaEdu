<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterParticipation extends Model
{
    use HasFactory;

    /**
     * Disable updated_at timestamp.
     */
    public const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'voter_participations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'election_id',
        'user_id',
    ];

    /**
     * Get the election that was participated in.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * Get the student user who participated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
