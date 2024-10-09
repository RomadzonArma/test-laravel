<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    use HasFactory;

    protected $table = 'sales_targets';
    protected $fillable = [
        'active_date',
        'amount',
        'sales_id'
    ];

    public function sale(){
        return $this->belongsTo(Sale::class);
    }
}
