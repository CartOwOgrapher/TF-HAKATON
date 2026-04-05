<?php

namespace Modules\Core\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Enums\Role;
use Modules\Xml\Models\XmlImportBatch;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }



    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isHR(): bool
    {
        return $this->role === Role::HR;
    }

    public function isTrainingCenter(): bool
    {
        return $this->role === Role::TrainingCenter;
    }

    public function isAccounting(): bool
    {
        return $this->role === Role::Accounting;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function processedXmlBatches(): HasMany
    {
        return $this->hasMany(XmlImportBatch::class,'processed_by');
    }
}
