<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Application extends Model
{
    protected $fillable = [
        'users_id',
        'internships_id',
        'approved_at',
        'motivation_letter',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internships_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public static function createForUser(int $userId, int $internshipId, ?string $motivationLetter = null): array
    {
        $user = User::findOrFail($userId);

        if (!$user->groups_id) {
            return ['success' => false, 'message' => 'User does not belong to any group', 'status' => 422];
        }

        $groupInternship = \App\Models\GroupInternship::where('group_id', $user->groups_id)
            ->where('internship_id', $internshipId)
            ->first();

        if (!$groupInternship) {
            return ['success' => false, 'message' => 'User\'s group is not associated with this internship', 'status' => 422];
        }

        if (self::where('users_id', $userId)->where('internships_id', $internshipId)->exists()) {
            return ['success' => false, 'message' => 'User has already applied to this internship', 'status' => 422];
        }

        $application = self::create([
            'users_id' => $userId,
            'internships_id' => $internshipId,
            'motivation_letter' => $motivationLetter,
        ]);

        return ['success' => true, 'application' => $application->fresh()];
    }
}
