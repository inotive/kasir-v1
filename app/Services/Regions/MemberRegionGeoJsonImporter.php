<?php

namespace App\Services\Regions;

use App\Models\MemberRegion;
use Illuminate\Support\Arr;

class MemberRegionGeoJsonImporter
{
    public function resolveProvinceNameFromCode(?string $code): ?string
    {
        $map = [
            '11' => 'Aceh',
            '12' => 'Sumatera Utara',
            '13' => 'Sumatera Barat',
            '14' => 'Riau',
            '15' => 'Jambi',
            '16' => 'Sumatera Selatan',
            '17' => 'Bengkulu',
            '18' => 'Lampung',
            '19' => 'Kepulauan Bangka Belitung',
            '21' => 'Kepulauan Riau',
            '31' => 'DKI Jakarta',
            '32' => 'Jawa Barat',
            '33' => 'Jawa Tengah',
            '34' => 'DI Yogyakarta',
            '35' => 'Jawa Timur',
            '36' => 'Banten',
            '51' => 'Bali',
            '52' => 'Nusa Tenggara Barat',
            '53' => 'Nusa Tenggara Timur',
            '61' => 'Kalimantan Barat',
            '62' => 'Kalimantan Tengah',
            '63' => 'Kalimantan Selatan',
            '64' => 'Kalimantan Timur',
            '65' => 'Kalimantan Utara',
            '71' => 'Sulawesi Utara',
            '72' => 'Sulawesi Tengah',
            '73' => 'Sulawesi Selatan',
            '74' => 'Sulawesi Tenggara',
            '75' => 'Gorontalo',
            '76' => 'Sulawesi Barat',
            '81' => 'Maluku',
            '82' => 'Maluku Utara',
            '91' => 'Papua',
            '92' => 'Papua Barat',
            '93' => 'Papua Selatan',
            '94' => 'Papua Tengah',
            '95' => 'Papua Pegunungan',
            '96' => 'Papua Barat Daya',
        ];

        if (! $code) {
            return null;
        }

        $key = str_pad(trim($code), 2, '0', STR_PAD_LEFT);

        return $map[$key] ?? null;
    }

    public function readRegencyMetaFromFile(string $filePath): array
    {
        $json = json_decode((string) file_get_contents($filePath), true);
        $first = Arr::get($json, 'features.0', []);
        $props = (array) Arr::get($first, 'properties', []);

        $provinceCode = (string) ($props['kd_propinsi'] ?? '');
        $regencyCode = (string) ($props['kd_dati2'] ?? '');
        $regencyName = (string) ($props['nm_dati2'] ?? '');
        $provinceName = $this->resolveProvinceNameFromCode($provinceCode);

        return [
            'province_code' => $provinceCode !== '' ? $provinceCode : null,
            'regency_code' => $regencyCode !== '' ? $regencyCode : null,
            'province_name' => $provinceName,
            'regency_name' => $regencyName !== '' ? $regencyName : null,
        ];
    }

    public function importDistrictsFromFile(string $filePath, string $provinceName, string $regencyName): int
    {
        $json = json_decode((string) file_get_contents($filePath), true);
        $features = Arr::get($json, 'features', []);
        if (! is_array($features)) {
            return 0;
        }

        $count = 0;

        foreach ($features as $feature) {
            $props = (array) Arr::get($feature, 'properties', []);
            $districtName = (string) ($props['nm_kecamatan'] ?? $props['kecamatan'] ?? $props['district'] ?? '');
            $districtName = trim($districtName);
            if ($districtName === '') {
                continue;
            }

            $geometry = Arr::get($feature, 'geometry');
            if (! is_array($geometry)) {
                continue;
            }

            $normalized = $this->normalizeGeometry($geometry);
            if ($normalized === null) {
                continue;
            }

            $geojson = [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [
                            'province' => $provinceName,
                            'regency' => $regencyName,
                            'district' => $districtName,
                        ],
                        'geometry' => $normalized,
                    ],
                ],
            ];

            MemberRegion::query()->updateOrCreate(
                [
                    'province' => $provinceName,
                    'regency' => $regencyName,
                    'district' => $districtName,
                ],
                [
                    'geojson' => json_encode($geojson, JSON_UNESCAPED_SLASHES),
                ],
            );

            $count++;
        }

        return $count;
    }

    private function normalizeGeometry(array $geometry): ?array
    {
        $type = (string) ($geometry['type'] ?? '');
        $coordinates = $geometry['coordinates'] ?? null;
        if ($type === '' || $coordinates === null) {
            return null;
        }

        $normalized = [
            'type' => $type,
            'coordinates' => $this->stripZ($coordinates),
        ];

        return $normalized;
    }

    private function stripZ(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $isPair = array_key_exists(0, $value) && array_key_exists(1, $value)
            && is_numeric($value[0]) && is_numeric($value[1]);

        if ($isPair) {
            return [(float) $value[0], (float) $value[1]];
        }

        return array_map(fn ($v) => $this->stripZ($v), $value);
    }
}
