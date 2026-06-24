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
        'amount',
        'note',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'integer',
            'created_by_user_id' => 'integer',
        ];
    }
}
