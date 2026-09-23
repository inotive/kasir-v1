<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'openwa_session_id',
        'openwa_session_name',
        'status',
        'linked_phone',
        'is_enabled',
        'send_on_checkout_default',
        'last_status_checked_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'send_on_checkout_default' => 'boolean',
            'last_status_checked_at' => 'datetime',
        ];
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->openwa_session_id !== null;
    }

    public static function current(): self
    {
        static $cached;
        if ($cached instanceof self) {
            $key = $cached->getKey();
            if ($key !== null && self::query()->whereKey($key)->exists()) {
                return $cached;
            }
            $cached = null;
        }
        $cached = self::query()->first();
        if (! $cached) {
            $cached = self::query()->create([
                'status' => 'not_configured',
                'is_enabled' => false,
                'send_on_checkout_default' => true,
            ]);
        }

        return $cached;
    }
}
