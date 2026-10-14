<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'election_id',
        'name',
        'description',
        'photo_path',
        'members',
        'proposals',
    ];

    /**
     * Get the election to which this option belongs.
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * Get the votes received by this option.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'option_id');
    }
}
