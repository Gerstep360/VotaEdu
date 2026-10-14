<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'grade',
        'parallel',
        'name',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'user_id')
            ->withTimestamps();
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherCourseAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_course_assignments', 'course_id', 'user_id')
            ->withTimestamps();
    }

    public function elections(): BelongsToMany
    {
        return $this->belongsToMany(Election::class, 'election_courses')
            ->withTimestamps();
    }
}
