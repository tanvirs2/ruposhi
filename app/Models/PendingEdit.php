<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class PendingEdit extends Model
{
    use HasShopScope;

    protected $fillable = [
        'shop_id', 'model_type', 'model_id', 'requested_by',
        'original_data', 'proposed_data', 'has_changes',
        'status', 'decided_by', 'decided_at', 'sms_sent',
    ];

    protected $casts = [
        'original_data' => 'array',
        'proposed_data' => 'array',
        'has_changes'   => 'boolean',
        'sms_sent'      => 'boolean',
        'decided_at'    => 'datetime',
    ];

    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function decidedBy()   { return $this->belongsTo(User::class, 'decided_by'); }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'model_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'model_id');
    }

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isNoChange(): bool  { return $this->status === 'no_change'; }
}
