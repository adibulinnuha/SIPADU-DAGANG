<?php

namespace App\Models;

use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role',
    'market_id', 'nip', 'rank', 'jabatan', 'phone', 'notes',
    'is_active', 'is_juru_pungut',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'is_juru_pungut' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isPetugas(): bool
    {
        return $this->role === UserRole::Petugas;
    }

    /**
     * The market this petugas (Korwil / Juru Pungut) is assigned to.
     *
     * @return BelongsTo<Market, $this>
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * @return HasMany<Retribution, $this>
     */
    public function recordedRetributions(): HasMany
    {
        return $this->hasMany(Retribution::class, 'recorded_by');
    }

    /**
     * Scope: active petugas (Korwil role) marked to appear in the ERET dropdown.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     */
    public function scopeActiveJuruPungut($query, ?int $marketId = null)
    {
        $scope = $query
            ->where('role', UserRole::Petugas)
            ->where('is_active', true)
            ->where('is_juru_pungut', true);

        if ($marketId !== null) {
            $scope->where('market_id', $marketId);
        }

        return $scope->orderBy('name');
    }
}

