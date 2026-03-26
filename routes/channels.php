<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('cashier.orders', function ($user) {
    return $user?->can('pos.access') ?? false;
});
