<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'email',
        'password',
        'role',
        'phone',
        'badge_id',
        'is_active',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function isCashier(): bool
    {
        return in_array($this->role, ['admin', 'manager', 'cashier']);
    }

    public function isPatrol(): bool
    {
        return in_array($this->role, ['admin', 'manager', 'patrol']);
    }

    public function hasRole(array|string $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return in_array($this->role, $roles);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'cashier_user_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(ParkingViolation::class, 'officer_user_id');
    }

    public function gateLogs(): HasMany
    {
        return $this->hasMany(GateLog::class, 'operator_user_id');
    }

    public function bulkImports(): HasMany
    {
        return $this->hasMany(BulkImport::class);
    }
}
