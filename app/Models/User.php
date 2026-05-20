<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
    ];

    /**
     * JWT Identifier
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Custom JWT Claims
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

     // User has many conversations
    public function conversations() {
        return $this->hasMany(Conversation::class, 'user_one_id')
            ->orWhere('user_two_id', $this->id);
    }

    // User has many messages sent
    public function messages() {
        return $this->hasMany(Message::class, 'sender_id');
    }

}