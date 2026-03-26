<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /** Spatie: нэвтрэхийн дараа hasRole() зөв guard-аар ажиллана. */
    protected string $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'workplace',
        'is_active',
        'phone',
        'register_number',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function hearingsAsJudge()
    {
        return $this->belongsToMany(\App\Models\Hearing::class, 'hearing_judges', 'judge_id', 'hearing_id');
    }
}
