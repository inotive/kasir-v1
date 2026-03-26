<?php

namespace App\Observers;

use App\Models\DiningTable;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DiningTableObserver
{
    public function created(DiningTable $table): void
    {
        $this->generateBarcode($table);
        $table->saveQuietly();
    }

    public function updating(DiningTable $table): void
    {
        $this->generateBarcode($table);
    }

    protected function generateBarcode(DiningTable $table): void
    {
        $id = (int) $table->id;
        $encoded = \generate_qr_code($id);
        $url = rtrim(config('app.url'), '/').'/t/'.$encoded;

        $svg = QrCode::margin(1)->size(200)->generate($url);
        $path = 'qr_codes/'.$id.'.svg';

        Storage::disk('public')->put($path, $svg);

        if ($table->image === $path && $table->qr_value === $url) {
            return;
        }

        $table->image = $path;
        $table->qr_value = $url;
    }
}
