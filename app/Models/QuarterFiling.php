<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class QuarterFiling extends Model
{
    use BelongsToUser;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
        'filed_at' => 'date',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
