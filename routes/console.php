<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('member-regions:import {districtGeojson} {--regency-geojson=} {--province=} {--regency=}', function () {
    $districtFile = (string) $this->argument('districtGeojson');
    $regencyFile = (string) ($this->option('regency-geojson') ?? '');
    $province = trim((string) ($this->option('province') ?? ''));
    $regency = trim((string) ($this->option('regency') ?? ''));

    $importer = new \App\Services\Regions\MemberRegionGeoJsonImporter;

    if ($regencyFile !== '' && file_exists($regencyFile)) {
        $meta = $importer->readRegencyMetaFromFile($regencyFile);
        if ($province === '' && (string) ($meta['province_name'] ?? '') !== '') {
            $province = (string) $meta['province_name'];
        }
        if ($regency === '' && (string) ($meta['regency_name'] ?? '') !== '') {
            $regency = (string) $meta['regency_name'];
        }
    }

    if ($province === '' || $regency === '') {
        $this->error('Provinsi dan kabupaten/kota wajib diisi, atau sertakan --regency-geojson untuk auto-detect.');

        return 1;
    }

    if (! file_exists($districtFile)) {
        $this->error('File tidak ditemukan: '.$districtFile);

        return 1;
    }

    $count = $importer->importDistrictsFromFile($districtFile, $province, $regency);

    if ($count <= 0) {
        $this->error('Import gagal atau tidak ada kecamatan yang valid.');

        return 1;
    }

    $this->info('Import selesai: '.$count.' kecamatan ('.$regency.', '.$province.').');

    return 0;
})->purpose('Import wilayah provinsi/kabupaten/kecamatan dari GeoJSON');

Schedule::command('vouchers:alert')->dailyAt('08:00');
