<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelStage extends Model
{
    protected $guarded = ['id'];

    public function funnel()
    {
        return $this->belongsTo(Funnel::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'funnel_stage_id');
    }
}
