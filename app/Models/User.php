<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CLIENTE = 'cliente';

    public const ROLE_ADMIN_CANCHA = 'admin_cancha';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const STATUS_ACTIVO = 'activo';

    public const STATUS_SUSPENDIDO = 'suspendido';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'last_login_at',
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function complexAssignments(): HasMany
    {
        return $this->hasMany(ComplexUserAssignment::class);
    }

    public function clientReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'client_user_id');
    }

    public function canceledReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'canceled_by_user_id');
    }

    public function createdTournaments(): HasMany
    {
        return $this->hasMany(ComplexTournament::class, 'created_by_user_id');
    }

    public function createdTeamBoardPosts(): HasMany
    {
        return $this->hasMany(ComplexTeamBoardPost::class, 'created_by_user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdminCancha(): bool
    {
        return $this->role === self::ROLE_ADMIN_CANCHA;
    }

    public function isCliente(): bool
    {
        return $this->role === self::ROLE_CLIENTE;
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(UserRanking::class);
    }
}
