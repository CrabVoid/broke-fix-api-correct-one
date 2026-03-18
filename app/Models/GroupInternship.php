<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupInternship extends Model
{
    protected $fillable = ['group_id', 'internship_id', 'start_at', 'end_at'];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internship_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, ['group_id', 'internship_id']);
    }
}
