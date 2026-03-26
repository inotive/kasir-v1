<?php

namespace App\Livewire\Members;

use App\Models\MemberRegion;
use App\Services\Regions\MemberRegionGeoJsonImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class MemberRegionsPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $title = 'Wilayah Member';

    public string $search = '';

    public bool $importModalOpen = false;

    public ?string $importProvince = null;

    public ?string $importRegency = null;

    public mixed $importRegencyGeojson = null;

    public mixed $importDistrictGeojson = null;

    public function mount(): void
    {
        $this->authorizeAny(['members.regions.view', 'members.regions.manage']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openImportModal(): void
    {
        $this->authorizePermission('members.regions.manage');

        $this->reset(['importProvince', 'importRegency', 'importRegencyGeojson', 'importDistrictGeojson']);
        $this->resetValidation();
        $this->importModalOpen = true;
    }

    public function closeImportModal(): void
    {
        $this->importModalOpen = false;
        $this->resetValidation();
    }

    protected function regionsQuery(): Builder
    {
        return MemberRegion::query()
            ->withCount('members')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('province', 'like', $term)
                        ->orWhere('regency', 'like', $term)
                        ->orWhere('district', 'like', $term);
                });
            })
            ->orderBy('province')
            ->orderBy('regency')
            ->orderBy('district');
    }

    public function importGeojson(): void
    {
        $this->authorizePermission('members.regions.manage');

        $validated = $this->validate([
            'importProvince' => ['nullable', 'string', 'max:255'],
            'importRegency' => ['nullable', 'string', 'max:255'],
            'importRegencyGeojson' => ['nullable', 'file', 'max:51200', 'mimes:json,geojson,txt'],
            'importDistrictGeojson' => ['required', 'file', 'max:51200', 'mimes:json,geojson,txt'],
        ]);

        $provinceName = trim((string) ($validated['importProvince'] ?? ''));
        $regencyName = trim((string) ($validated['importRegency'] ?? ''));

        $importer = new MemberRegionGeoJsonImporter;

        if ($this->importRegencyGeojson) {
            $meta = $importer->readRegencyMetaFromFile((string) $this->importRegencyGeojson->getRealPath());
            if ($provinceName === '' && (string) ($meta['province_name'] ?? '') !== '') {
                $provinceName = (string) $meta['province_name'];
            }
            if ($regencyName === '' && (string) ($meta['regency_name'] ?? '') !== '') {
                $regencyName = (string) $meta['regency_name'];
            }
        }

        if ($provinceName === '') {
            $this->addError('importProvince', 'Provinsi wajib diisi atau diambil dari file kabupaten.');

            return;
        }

        if ($regencyName === '') {
            $this->addError('importRegency', 'Kabupaten/Kota wajib diisi atau diambil dari file kabupaten.');

            return;
        }

        $count = $importer->importDistrictsFromFile(
            (string) $this->importDistrictGeojson->getRealPath(),
            $provinceName,
            $regencyName,
        );

        if ($count <= 0) {
            $this->addError('importDistrictGeojson', 'Tidak ada kecamatan valid yang berhasil diimport.');

            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Import selesai: '.$count.' kecamatan.');

        $this->reset(['importProvince', 'importRegency', 'importRegencyGeojson', 'importDistrictGeojson']);
        $this->resetValidation();
        $this->resetPage();
        $this->importModalOpen = false;
    }

    public function deleteRegion(int $regionId): void
    {
        $this->authorizePermission('members.regions.manage');

        $region = MemberRegion::query()->withCount('members')->findOrFail($regionId);

        if ((int) $region->members_count > 0) {
            $this->addError('regions', 'Wilayah tidak bisa dihapus karena sudah dipakai member.');

            return;
        }

        $region->delete();
    }

    public function render(): View
    {
        $this->authorizeAny(['members.regions.view', 'members.regions.manage']);

        $regions = $this->regionsQuery()->paginate(15);

        return view('livewire.members.member-regions-page', [
            'regions' => $regions,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }

    private function authorizePermission(string $permission): void
    {
        $permission = trim($permission);
        if ($permission === '') {
            abort(403);
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can') || ! $user->can($permission)) {
            abort(403);
        }
    }
}
