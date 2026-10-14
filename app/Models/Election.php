<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'type', // curso, institucional, eleccion_estudiantil
        'academic_year_id',
        'description',
        'status', // pending, open, closed, cancelled
        'cancellation_reason',
        'created_by',
        'parent_election_id',
        'opened_at',
        'closed_at',
        'scheduled_open_at',
        'scheduled_close_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'scheduled_open_at' => 'datetime',
            'scheduled_close_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'election_courses')
            ->withTimestamps();
    }

    public function eligibleVoters(): HasMany
    {
        return $this->hasMany(ElectionEligibleVoter::class);
    }

    public function parentElection(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_election_id');
    }

    public function runoffElections(): HasMany
    {
        return $this->hasMany(self::class, 'parent_election_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class, 'election_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'election_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(VoterParticipation::class, 'election_id');
    }

    // Type helpers
    public function isCourseElection(): bool
    {
        return $this->type === 'curso';
    }

    public function isInstitutional(): bool
    {
        return $this->type === 'institucional';
    }

    public function isStudentElection(): bool
    {
        return $this->type === 'eleccion_estudiantil';
    }

    // Status helpers
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'borrador'], true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'abierta'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'cerrada'], true);
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'anulada'], true);
    }

    public function canBeOpened(): bool
    {
        return $this->isPending() && $this->options()->count() >= 2;
    }

    public function hasStudentVoted(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participations()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a given user is eligible to vote in this election.
     */
    public function isUserEligible(User $user): bool
    {
        if (! $user->isStudent() || ! $user->is_active) {
            return false;
        }

        // If the electoral roll has been frozen, check the frozen snapshot
        if ($this->eligibleVoters()->exists()) {
            return $this->eligibleVoters()->where('user_id', $user->id)->exists();
        }

        // If scoped to courses, check student's enrolled courses
        if ($this->courses()->exists()) {
            $courseIds = $this->courses()->pluck('courses.id')->toArray();

            return $user->enrolledCourses()->whereIn('courses.id', $courseIds)->exists();
        }

        // Institutional election applies to all active students
        return true;
    }

    /**
     * Freeze the electoral roll when opening the election.
     */
    public function freezeEligibleVoters(): void
    {
        $eligibleUserIds = collect();

        if ($this->courses()->exists()) {
            $courseIds = $this->courses()->pluck('courses.id');
            $eligibleUserIds = CourseEnrollment::whereIn('course_id', $courseIds)
                ->join('users', 'users.id', '=', 'course_enrollments.user_id')
                ->where('users.is_active', true)
                ->pluck('user_id')
                ->unique();
        } else {
            $eligibleUserIds = User::where(function ($q) {
                $q->where('role', 'student')->orWhere('role', 'estudiante');
            })->where('is_active', true)->pluck('id');
        }

        $records = $eligibleUserIds->map(fn ($userId) => [
            'election_id' => $this->id,
            'user_id' => $userId,
            'created_at' => now(),
        ])->toArray();

        if (! empty($records)) {
            ElectionEligibleVoter::insertOrIgnore($records);
        }
    }

    /**
     * Check if current time falls within scheduled dates.
     */
    public function isWithinScheduledTime(): bool
    {
        $now = now();

        if ($this->scheduled_open_at && $now->lt($this->scheduled_open_at)) {
            return false;
        }

        if ($this->scheduled_close_at && $now->gt($this->scheduled_close_at)) {
            return false;
        }

        return true;
    }
}
