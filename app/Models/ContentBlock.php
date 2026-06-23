<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'content' => 'array', // Автоматически преобразует JSON в массив
        'is_visible' => 'boolean',
    ];

    protected $attributes = [
        'content' => '{}',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (is_null($model->content)) {
                $model->content = [];
            }
        });
    }

    public function testResults()
{
    return $this->hasMany(TestResult::class, 'content_block_id');
}
}