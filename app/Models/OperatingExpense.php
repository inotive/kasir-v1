<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OperatingExpense extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'expense_date',
        'category',
        'quantity',
        'unit',
        'unit_cost',
        'amount',
        'note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'amount' => 'integer',
            'created_by_user_id' => 'integer',
        ];
    }
}
