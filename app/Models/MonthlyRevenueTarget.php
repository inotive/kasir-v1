<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRevenueTarget extends Model
{
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
