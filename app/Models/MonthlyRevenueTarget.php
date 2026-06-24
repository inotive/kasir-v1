<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MonthlyRevenueTarget extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'year',
        'month',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'amount' => 'integer',
        ];
    }
}
