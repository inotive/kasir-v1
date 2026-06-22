<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrinterSource extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
