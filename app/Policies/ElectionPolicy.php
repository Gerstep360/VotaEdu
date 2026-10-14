<?php

namespace App\Policies;

use App\Models\Election;
use App\Models\User;

class ElectionPolicy
{
    public function export(User $user, Election $election): bool
    {
        return $election->isClosed() && ($user->canManageSystem()
            || ($user->isTeacher() && $this->view($user, $election)));
    }

    /**
     * Determine whether the user can view any elections.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the election details.
     */
    public function view(User $user, Election $election): bool
    {
        if ($user->canManageSystem()) {
            return true;
        }

        if ($user->isTeacher()) {
            // Teacher can view their own created elections or elections in their assigned courses
            if ($election->created_by === $user->id) {
                return true;
            }

            $assignedCourseIds = $user->assignedCourses()->pluck('courses.id')->toArray();

            return $election->courses()->whereIn('courses.id', $assignedCourseIds)->exists();
        }

        if ($user->isStudent()) {
            return $election->isOpen() || $election->isClosed();
        }

        return false;
    }

    /**
     * Determine whether the user can create elections.
     */
    public function create(User $user): bool
    {
        return $user->canManageSystem() || $user->isTeacher();
    }

    /**
     * Determine whether the user can update the election draft.
     */
    public function update(User $user, Election $election): bool
    {
        if (! $election->isPending()) {
            return false;
        }

        if ($user->canManageSystem()) {
            return true;
        }

        return $user->isTeacher() && $election->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete an election draft.
     */
    public function delete(User $user, Election $election): bool
    {
        if (! $election->isPending() || $election->votes()->exists() || $election->participations()->exists()) {
            return false;
        }

        if ($user->canManageSystem()) {
            return true;
        }

        return $user->isTeacher() && $election->created_by === $user->id;
    }

    /**
     * Determine whether the user can open the election.
     */
    public function open(User $user, Election $election): bool
    {
        if (! $election->canBeOpened()) {
            return false;
        }

        if ($user->canManageSystem()) {
            return true;
        }

        return $user->isTeacher() && $election->created_by === $user->id;
    }

    /**
     * Determine whether the user can close the election.
     */
    public function close(User $user, Election $election): bool
    {
        if (! $election->isOpen()) {
            return false;
        }

        if ($user->canManageSystem()) {
            return true;
        }

        return $user->isTeacher() && $election->created_by === $user->id;
    }

    /**
     * Determine whether the user can cancel the election.
     */
    public function cancel(User $user, Election $election): bool
    {
        return $user->canManageSystem() && ! $election->isCancelled();
    }

    /**
     * Determine whether the user can duplicate the election into a new draft.
     */
    public function duplicate(User $user, Election $election): bool
    {
        if ($user->canManageSystem()) {
            return true;
        }

        return $user->isTeacher() && $election->created_by === $user->id;
    }

    /**
     * Determine whether the user can vote in the election.
     */
    public function vote(User $user, Election $election): bool
    {
        if (! $user->isStudent() || ! $user->is_active) {
            return false;
        }

        if (! $election->isOpen() || ! $election->isWithinScheduledTime()) {
            return false;
        }

        if ($election->hasStudentVoted($user)) {
            return false;
        }

        return $election->isUserEligible($user);
    }
}
