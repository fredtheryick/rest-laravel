<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    /**
     * Get the orders for the customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    /**
     * Scope address from query
     */
    public function scopeAddressLike($query, $string = null)
    {
        if ($string != null) {
            return $query->where('address', 'like', '%' . $string . '%');
        } else {
            return $query;
        }
    }
    
    /**
     * Scope point from query
     */
    public function scopePointLike($query, $number = null)
    {
        if ($number != null) {
            return $query->where('point', '>=', $number);
        } else {
            return $query;
        }
    }
}
