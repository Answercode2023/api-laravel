<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType  = 'string';

    protected $fillable = [
        'user_id',
        'to_user_id',
        'type',
        'value',
        'description',
        'related_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    /** quem gerou a transação (ex.: quem transferiu ou fez depósito) */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** destinatário (só existe quando type = transfer ou receive) */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /** transação relacionada (usada em reversões) */
    public function related()
    {
        return $this->belongsTo(Transaction::class, 'related_id');
    }
}
