<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Balance extends Model
{
    use HasUuids;               // gera UUID automaticamente
    public $incrementing = false;
    protected $keyType  = 'string';
    protected $primaryKey = 'user_id';   // 1‑to‑1 com usuário

    protected $fillable = [
        'user_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /** relacionamento: um saldo pertence a um usuário */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
