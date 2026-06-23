<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funnel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stages()
    {
        return $this->hasMany(FunnelStage::class)->orderBy('sort_order');
    }

    protected static function booted()
    {
        static::created(function (Funnel $funnel) {
            $defaultStages = [
                [
                    'name' => 'Новая заявка',
                    'type' => 'regular',
                    'sort_order' => 10,
                    'color' => '#94a3b8', // Gray
                ],
                [
                    'name' => 'В работе',
                    'type' => 'regular',
                    'sort_order' => 20,
                    'color' => '#3b82f6', // Blue
                ],
                [
                    'name' => 'Принято решение',
                    'type' => 'regular',
                    'sort_order' => 30,
                    'color' => '#eab308', // Yellow
                ],
                [
                    'name' => 'Успешно реализован',
                    'type' => 'won',
                    'sort_order' => 100,
                    'color' => '#10b981', // Green
                ],
                [
                    'name' => 'Нереализован',
                    'type' => 'lost',
                    'sort_order' => 110,
                    'color' => '#ef4444', // Red
                ],
            ];

            foreach ($defaultStages as $stage) {
                $funnel->stages()->create($stage);
            }
        });
    }
}
