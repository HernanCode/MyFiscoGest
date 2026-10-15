<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    use BelongsToUser, HasFactory;

    protected $guarded = [];

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function filings()
    {
        return $this->hasMany(QuarterFiling::class);
    }

    protected static function booted(): void
    {
        // Al crear el año, se generan los 8 vencimientos (4 trimestres x 2 modelos).
        static::created(function (FiscalYear $year) {
            $deadlines = [
                1 => "{$year->year}-04-20",
                2 => "{$year->year}-07-20",
                3 => "{$year->year}-10-20",
                4 => ($year->year + 1).'-01-30',
            ];

            foreach ($deadlines as $quarter => $date) {
                foreach (['303', '130'] as $model) {
                    $year->filings()->create([
                        'user_id' => $year->user_id,
                        'quarter' => $quarter,
                        'model' => $model,
                        'due_date' => $date,
                    ]);
                }
            }
        });
    }
}
