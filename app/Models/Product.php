<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $fillable = [
        'name',
        'production_price',
        'selling_price'
    ];

    public function salesOrderItem()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
