<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Models\MemberRegion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MembersPage extends Component
{
    use WithPagination;

    public string $title = 'Member';

    public string $search = '';

    public bool $createMemberModalOpen = false;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $memberRegionId = null;

    public ?string $province = null;

    public ?string $regency = null;

    public ?string $points = null;

    public ?int $editingMemberId = null;

    public string $editingName = '';

    public ?string $editingEmail = null;

    public ?string $editingPhone = null;

    public ?string $editingMemberRegionId = null;

    public ?string $editingProvince = null;

    public ?string $editingRegency = null;

    public ?string $editingPoints = null;

    public function mount(): void
    {
        $this->authorizePermission('members.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateMemberModal(): void
    {
        $this->authorizePermission('members.create');
        $this->authorizePermission('members.pii.view');

        $this->reset(['name', 'email', 'phone', 'memberRegionId', 'province', 'regency', 'points']);
        $this->resetValidation();
        $this->createMemberModalOpen = true;
    }

    public function closeCreateMemberModal(): void
    {
        $this->createMemberModalOpen = false;
        $this->resetValidation();
    }

    public function updatedProvince(): void
    {
        $this->reset(['regency', 'memberRegionId']);
    }

    public function updatedRegency(): void
    {
        $this->reset(['memberRegionId']);
    }

    public function updatedEditingProvince(): void
    {
        $this->reset(['editingRegency', 'editingMemberRegionId']);
    }

    public function updatedEditingRegency(): void
    {
        $this->reset(['editingMemberRegionId']);
    }

    protected function membersQuery(): Builder
    {
        $canViewPii = $this->canViewPii();

        return Member::query()
            ->with('region')
            ->withCount('transactions')
            ->when($this->search !== '', function (Builder $query) use ($canViewPii): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term, $canViewPii): void {
                    $q->where('name', 'like', $term);

                    if ($canViewPii) {
                        $q->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    }
                });
            })
            ->orderByDesc('id');
    }

    public function createMember(): void
    {
        $this->authorizePermission('members.create');
        $this->authorizePermission('members.pii.view');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('members', 'email')],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('members', 'phone')],
            'memberRegionId' => ['nullable', 'integer', 'exists:member_regions,id'],
            'points' => ['nullable', 'integer', 'min:0'],
        ]);

        Member::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'] !== '' ? $validated['email'] : null,
            'phone' => $validated['phone'] !== '' ? $validated['phone'] : null,
            'member_region_id' => $validated['memberRegionId'] === null || $validated['memberRegionId'] === '' ? null : (int) $validated['memberRegionId'],
            'points' => $validated['points'] === null || $validated['points'] === '' ? 0 : (int) $validated['points'],
        ]);

        $this->reset(['name', 'email', 'phone', 'memberRegionId', 'province', 'regency', 'points']);
        $this->resetValidation();
        $this->resetPage();
        $this->createMemberModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Member berhasil ditambahkan.');
    }

    public function startEditMember(int $memberId): void
    {
        $this->authorizePermission('members.edit');
        $this->authorizePermission('members.pii.view');

        $member = Member::query()->with('region')->findOrFail($memberId);

        $this->editingMemberId = (int) $member->id;
        $this->editingName = (string) $member->name;
        $this->editingEmail = $member->email;
        $this->editingPhone = $member->phone;
        $this->editingMemberRegionId = $member->member_region_id === null ? null : (string) $member->member_region_id;
        $this->editingProvince = $member->region ? (string) $member->region->province : null;
        $this->editingRegency = $member->region ? (string) $member->region->regency : null;
        $this->editingPoints = (string) ((int) $member->points);
        $this->resetValidation();
    }

    public function cancelEditMember(): void
    {
        $this->editingMemberId = null;
        $this->reset(['editingName', 'editingEmail', 'editingPhone', 'editingMemberRegionId', 'editingProvince', 'editingRegency', 'editingPoints']);
        $this->resetValidation();
    }

    public function updateMember(): void
    {
        $this->authorizePermission('members.edit');
        $this->authorizePermission('members.pii.view');

        if (! $this->editingMemberId) {
            return;
        }

        $validated = $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingEmail' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($this->editingMemberId),
            ],
            'editingPhone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('members', 'phone')->ignore($this->editingMemberId),
            ],
            'editingMemberRegionId' => ['nullable', 'integer', 'exists:member_regions,id'],
            'editingPoints' => ['nullable', 'integer', 'min:0'],
        ]);

        Member::query()
            ->whereKey($this->editingMemberId)
            ->update([
                'name' => $validated['editingName'],
                'email' => $validated['editingEmail'] !== '' ? $validated['editingEmail'] : null,
                'phone' => $validated['editingPhone'] !== '' ? $validated['editingPhone'] : null,
                'member_region_id' => $validated['editingMemberRegionId'] === null || $validated['editingMemberRegionId'] === '' ? null : (int) $validated['editingMemberRegionId'],
                'points' => $validated['editingPoints'] === null || $validated['editingPoints'] === '' ? 0 : (int) $validated['editingPoints'],
            ]);

        $this->cancelEditMember();
    }

    public function deleteMember(int $memberId): void
    {
        $this->authorizePermission('members.delete');

        $member = Member::query()->withCount('transactions')->findOrFail($memberId);

        if ((int) $member->transactions_count > 0) {
            $this->addError('members', 'Member tidak bisa dihapus karena sudah digunakan pada transaksi.');

            return;
        }

        $member->delete();

        if ($this->editingMemberId === $memberId) {
            $this->cancelEditMember();
        }
    }

    public function render(): View
    {
        $this->authorizePermission('members.view');

        $members = $this->membersQuery()->paginate(15);
        $provinces = MemberRegion::query()
            ->select('province')
            ->whereNotNull('district')
            ->distinct()
            ->orderBy('province')
            ->pluck('province')
            ->map(fn ($v) => (string) $v)
            ->all();

        $regencies = $this->province
            ? MemberRegion::query()
                ->select('regency')
                ->where('province', $this->province)
                ->whereNotNull('district')
                ->distinct()
                ->orderBy('regency')
                ->pluck('regency')
                ->map(fn ($v) => (string) $v)
                ->all()
            : [];

        $districts = ($this->province && $this->regency)
            ? MemberRegion::query()
                ->where('province', $this->province)
                ->where('regency', $this->regency)
                ->whereNotNull('district')
                ->orderBy('district')
                ->get(['id', 'district'])
            : collect();

        $editingRegencies = $this->editingProvince
            ? MemberRegion::query()
                ->select('regency')
                ->where('province', $this->editingProvince)
                ->whereNotNull('district')
                ->distinct()
                ->orderBy('regency')
                ->pluck('regency')
                ->map(fn ($v) => (string) $v)
                ->all()
            : [];

        $editingDistricts = ($this->editingProvince && $this->editingRegency)
            ? MemberRegion::query()
                ->where('province', $this->editingProvince)
                ->where('regency', $this->editingRegency)
                ->whereNotNull('district')
                ->orderBy('district')
                ->get(['id', 'district'])
            : collect();

        return view('livewire.members.members-page', [
            'members' => $members,
            'provinces' => $provinces,
            'regencies' => $regencies,
            'districts' => $districts,
            'editingRegencies' => $editingRegencies,
            'editingDistricts' => $editingDistricts,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function canViewPii(): bool
    {
        $user = auth()->user();

        return (bool) (($user && method_exists($user, 'can')) ? $user->can('members.pii.view') : false);
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
