<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'history_log' => 'array',
        'paid_at' => 'datetime',
        'utm_data' => 'array',
        'funnel_stage_id' => 'integer',
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function orderNotes()
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at', 'desc');
    }

    public function tariff()
    {
        return $this->belongsTo(Tariff::class);
    }

    public function funnelStage()
    {
        return $this->belongsTo(FunnelStage::class, 'funnel_stage_id');
    }

    protected static function booted()
    {
        static::creating(function (Order $order) {
            if (!$order->funnel_stage_id) {
                $activeFunnel = Funnel::where('is_active', true)->first();
                if ($activeFunnel) {
                    $firstStage = $activeFunnel->stages()->first();
                    if ($firstStage) {
                        $order->funnel_stage_id = $firstStage->id;
                    }
                }
            }
        });

        static::saving(function (Order $order) {
            // 1. Если меняется этап воронки, обновляем статус заказа
            if ($order->isDirty('funnel_stage_id') && $order->funnel_stage_id) {
                $stage = FunnelStage::find($order->funnel_stage_id);
                if ($stage) {
                    if ($stage->type === 'won') {
                        $order->status = 'paid';
                        if (!$order->paid_at) {
                            $order->paid_at = now();
                        }
                    } elseif ($stage->type === 'lost') {
                        $order->status = 'cancelled';
                    }
                }
            }
            // 2. Если меняется статус заказа, переносим на соответствующий этап воронки (won/lost)
            elseif ($order->isDirty('status')) {
                if ($order->status === 'paid') {
                    $wonStage = null;
                    if ($order->funnel_stage_id) {
                        $currentStage = FunnelStage::find($order->funnel_stage_id);
                        if ($currentStage && $currentStage->type !== 'won') {
                             $wonStage = FunnelStage::where('funnel_id', $currentStage->funnel_id)
                                 ->where('type', 'won')
                                 ->first();
                        }
                    } else {
                        $activeFunnel = Funnel::where('is_active', true)->first();
                        if ($activeFunnel) {
                            $wonStage = $activeFunnel->stages()->where('type', 'won')->first();
                        }
                    }
                    if ($wonStage) {
                        $order->funnel_stage_id = $wonStage->id;
                    }
                } elseif (in_array($order->status, ['cancelled', 'refund'])) {
                    $lostStage = null;
                    if ($order->funnel_stage_id) {
                        $currentStage = FunnelStage::find($order->funnel_stage_id);
                        if ($currentStage && $currentStage->type !== 'lost') {
                            $lostStage = FunnelStage::where('funnel_id', $currentStage->funnel_id)
                                ->where('type', 'lost')
                                ->first();
                        }
                    } else {
                        $activeFunnel = Funnel::where('is_active', true)->first();
                        if ($activeFunnel) {
                            $lostStage = $activeFunnel->stages()->where('type', 'lost')->first();
                        }
                    }
                    if ($lostStage) {
                        $order->funnel_stage_id = $lostStage->id;
                    }
                }
            }
        });
    }
}