<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'ci',
        'first_name',
        'paternal_surname',
        'maternal_surname',
        'password',
        'role',
        'course',
        'parallel',
        'is_active',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Participations recorded for the user.
     */
    public function participations(): HasMany
    {
        return $this->hasMany(VoterParticipation::class, 'user_id');
    }

    /**
     * Elections created by this user.
     */
    public function createdElections(): HasMany
    {
        return $this->hasMany(Election::class, 'created_by');
    }

    /**
     * Course enrollments for student accounts.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'user_id');
    }

    /**
     * Courses in which this student is enrolled.
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_enrollments', 'user_id', 'course_id')
            ->withTimestamps();
    }

    /**
     * Assignments for teacher accounts.
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherCourseAssignment::class, 'user_id');
    }

    /**
     * Courses assigned to this teacher.
     */
    public function assignedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'teacher_course_assignments', 'user_id', 'course_id')
            ->withTimestamps();
    }

    /**
     * Elections where the student is in the eligible voters roll.
     */
    public function eligibleElections(): BelongsToMany
    {
        return $this->belongsToMany(Election::class, 'election_eligible_voters', 'user_id', 'election_id');
    }

    // Role helpers
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'administrador'], true);
    }

    public function isDirector(): bool
    {
        return $this->role === 'director';
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['docente', 'teacher'], true);
    }

    public function isStudent(): bool
    {
        return in_array($this->role, ['student', 'estudiante'], true);
    }

    public function canManageSystem(): bool
    {
        return $this->isAdmin() || $this->isDirector();
    }

    public function hasRole(string ...$roles): bool
    {
        foreach ($roles as $role) {
            $normalized = match ($role) {
                'admin', 'administrador' => $this->isAdmin(),
                'director' => $this->isDirector(),
                'docente', 'teacher' => $this->isTeacher(),
                'student', 'estudiante' => $this->isStudent(),
                default => $this->role === $role,
            };

            if ($normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user has participated in a given election.
     */
    public function hasVotedIn(Election|int $election): bool
    {
        $electionId = $election instanceof Election ? $election->id : $election;

        return $this->participations()->where('election_id', $electionId)->exists();
    }

    /**
     * Return partially masked CI for privacy (e.g. 1234****).
     */
    public function getMaskedCiAttribute(): ?string
    {
        if (! $this->ci) {
            return null;
        }

        $length = strlen($this->ci);
        if ($length <= 4) {
            return $this->ci;
        }

        return substr($this->ci, 0, 4).str_repeat('*', $length - 4);
    }

    /**
     * Generate temporary password according to Section 3.1 specification:
     * [1ra letra paterno MAYUSCULA] + [1ra letra materno minúscula] + "." + [CI]
     */
    public static function generateTemporaryPassword(string $paternal, ?string $maternal, string $ci): string
    {
        $cleanPaternal = trim($paternal);
        $cleanMaternal = $maternal ? trim($maternal) : '';
        $cleanCi = trim($ci);

        $firstPaternal = ! empty($cleanPaternal) ? mb_strtoupper(mb_substr($cleanPaternal, 0, 1, 'UTF-8'), 'UTF-8') : 'X';
        $firstMaternal = ! empty($cleanMaternal) ? mb_strtolower(mb_substr($cleanMaternal, 0, 1, 'UTF-8'), 'UTF-8') : '';

        return $firstPaternal.$firstMaternal.'.'.$cleanCi;
    }
}
