<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'groups_id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'groups_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'users_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'users_id');
    }
}
