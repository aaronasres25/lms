<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Hierarchy: each role implies all lower ones (Odoo implied_ids chain). */
    public const ROLES = ['reader' => 0, 'user' => 1, 'qa' => 2, 'manager' => 3, 'admin' => 4];

    protected $fillable = ['name', 'email', 'password', 'role', 'active', 'last_login_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'active'            => 'boolean',
        ];
    }

    public function level(): int
    {
        return self::ROLES[$this->role] ?? 0;
    }

    public function atLeast(string $role): bool
    {
        return $this->level() >= (self::ROLES[$role] ?? 99);
    }

    /** Back-office access (Odoo "internal user"). */
    public function isStaff(): bool
    {
        return $this->atLeast('user');
    }

    protected $rightsCache = null;

    public function member() { return $this->hasOne(Member::class); }
    public function accessRights() { return $this->hasMany(AccessRight::class); }

    /**
     * CRUD check: admin = always; else the user's custom row for that resource; else the role default
     * from config('lms.permissions').
     */
    public function canDo(string $resource, string $action = 'read'): bool
    {
        if (! $this->active) return false;
        if ($this->role === 'admin') return true;

        $this->rightsCache ??= $this->accessRights()->get()->keyBy('resource');
        if ($row = $this->rightsCache->get($resource)) {
            return (bool) $row->{'can_' . $action};
        }

        $min = config("lms.permissions.{$resource}.{$action}");
        return $min !== null && $this->level() >= (self::ROLES[$min] ?? 99);
    }

    public function hasCustomRights(): bool
    {
        return $this->accessRights()->exists();
    }
    public function viewers() { return $this->hasMany(Viewer::class); }

    public function scopeStaff($q)
    {
        return $q->where('active', true)->where('role', '!=', 'reader');
    }
}
