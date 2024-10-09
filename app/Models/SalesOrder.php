<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $table = 'sales_orders';
    protected $fillable = [
        'reference_no',
        'sales_id',
        'customer_id'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sales_id');
    }


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class, 'order_id');
    }
}
