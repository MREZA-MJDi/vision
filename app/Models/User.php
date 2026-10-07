<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist()
    {
        return $this->hasOne(Wishlist::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'created_by');
    }

    public function financialTransactions()
    {
        return $this->hasMany(FinancialTransaction::class, 'created_by');
    }

    public function media()
    {
        return $this->hasMany(Media::class, 'uploaded_by');
    }

    public function scopeCustomers($query)
    {
        return $query->where('is_admin', false);
    }

    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    public function isCustomer(): bool
    {
        return $this->is_admin === false;
    }
}
