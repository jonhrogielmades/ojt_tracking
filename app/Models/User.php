<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Notifications\QueuedResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'student_id', 'program', 'section', 'offline_storage_key', 'password', 'role', 'is_active', 'mfa_enabled'])]
#[Hidden(['password', 'remember_token', 'mfa_code_hash', 'mfa_expires_at', 'mfa_last_sent_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function isAdmin(): bool
    {
        return $this->role->isAdministrator();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', UserRole::Student->value);
    }

    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->whereIn('role', array_map(
            static fn (UserRole $role): string => $role->value,
            UserRole::administratorRoles(),
        ));
    }

    public function hasCapability(string $capability): bool
    {
        return $this->is_active && $this->role->allows($capability);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPasswordNotification((string) $token));
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function dtrEntries(): HasMany
    {
        return $this->hasMany(DtrEntry::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

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
            'mfa_enabled' => 'boolean',
            'mfa_expires_at' => 'datetime',
            'mfa_last_sent_at' => 'datetime',
        ];
    }
}
