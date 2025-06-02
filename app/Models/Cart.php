<?php

namespace App\Models;

class Cart extends BaseModel
{
    protected $table = 'carts';
    
    protected $fillable = [
        'session_id',
        'goods_id', 
        'quantity'
    ];

    /**
     * 關聯商品
     */
    public function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }
} 