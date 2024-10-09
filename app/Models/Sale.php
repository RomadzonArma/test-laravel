<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $table = 'sales';
    protected $fillable = [
        'user_id',
        'area_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(SalesArea::class, 'area_id', 'id');
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class, 'sales_id');
    }


    public function salesTargets()
    {
        return $this->hasMany(SalesTarget::class);
    }
}
