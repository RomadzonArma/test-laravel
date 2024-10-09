<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    use HasFactory;

    protected $table = 'sales_order_items';
    protected $fillable = [
        'quantity',
        'production_price',
        'selling_price',
        'product_id',
        'order_id'
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function salesOrder(){
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }
}
