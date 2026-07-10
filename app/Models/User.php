<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'address',
        'email',
        'password',
        'role',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isProfileComplete(): bool
    {
        return !is_null($this->first_name) &&
               !is_null($this->last_name) &&
               !is_null($this->phone) &&
               !is_null($this->address);
    }
    protected static function booted()
{
    static::created(function ($user) {
        $user->wallet()->create([
            'balance' => 0,
            'total_deposited' => 0,
            'total_spent' => 0,
        ]);
    });
}
}
