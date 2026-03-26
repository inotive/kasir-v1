<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Traits\RequiresMemberSession;
use App\Mail\MemberVerificationMail;
use App\Models\Member;
use App\Models\MemberRegion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MemberProfileEditPage extends Component
{
    use RequiresMemberSession;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $province = '';

    public string $regency = '';

    public string $district = '';

    public array $provinces = [];

    public array $regencies = [];

    public array $districts = [];

    public ?string $notice = null;

    public int $memberId = 0;

    public function mount(): void
    {
        $member = $this->redirectIfNotMember();
        if (! $member) {
            return;
        }

        $this->memberId = (int) $member->id;
        $this->name = (string) $member->name;
        $this->email = (string) ($member->email ?? '');
        $this->phone = (string) ($member->phone ?? '');

        $region = $member->region;
        $this->province = (string) ($region?->province ?? '');
        $this->regency = (string) ($region?->regency ?? '');
        $this->district = (string) ($region?->district ?? '');

        $this->provinces = cache()->remember('member_regions.provinces', 3600, function (): array {
            return MemberRegion::query()
                ->select('province')
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->distinct()
                ->orderBy('province')
                ->pluck('province')
                ->map(fn ($p) => (string) $p)
                ->toArray();
        });

        $this->hydrateRegenciesAndDistricts();
    }

    public function updatedProvince($value): void
    {
        $this->province = (string) $value;
        $this->regency = '';
        $this->district = '';
        $this->districts = [];
        $this->notice = null;
        $this->resetErrorBag();

        $this->regencies = $this->getRegencies($this->province);
    }

    public function updatedRegency($value): void
    {
        $this->regency = (string) $value;
        $this->district = '';
        $this->notice = null;
        $this->resetErrorBag();

        $this->districts = $this->getDistricts($this->province, $this->regency);
    }

    public function save(): void
    {
        $this->notice = null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'province' => ['required', 'string', 'max:255'],
            'regency' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
        ]);

        if ($this->memberId <= 0) {
            $this->redirectRoute('self-order.start', navigate: true);

            return;
        }

        $member = Member::query()->with('region')->find((int) $this->memberId);
        if (! $member) {
            $this->redirectRoute('self-order.start', navigate: true);

            return;
        }

        $existingEmail = (string) ($member->email ?? '');
        $existingPhone = (string) ($member->phone ?? '');

        $this->validate([
            'email' => ['unique:members,email,'.(int) $member->id],
            'phone' => ['unique:members,phone,'.(int) $member->id],
        ]);

        $region = MemberRegion::query()
            ->where('province', $validated['province'])
            ->where('regency', $validated['regency'])
            ->where('district', $validated['district'])
            ->first();

        if (! $region) {
            $this->addError('district', 'Wilayah tidak valid. Silakan pilih ulang.');

            return;
        }

        $member->name = $validated['name'];
        $member->phone = $this->normalizePhone($validated['phone']);
        $member->member_region_id = (int) $region->id;

        $emailChanged = trim($validated['email']) !== '' && trim($validated['email']) !== $existingEmail;
        if ($emailChanged) {
            $member->email = $validated['email'];
            $member->verification_token = Str::random(40);
            $member->email_verified_at = null;
        }

        $member->save();

        session([
            'name' => (string) $member->name,
            'phone' => (string) ($member->phone ?? ''),
            'email' => (string) ($member->email ?? ''),
        ]);

        if ($emailChanged) {
            Mail::to($member->email)->queue(new MemberVerificationMail($member));
            $this->notice = 'Profil berhasil diperbarui. Silakan cek email untuk verifikasi email baru.';
        } else {
            $this->notice = 'Profil berhasil diperbarui.';
        }

        if ($existingPhone !== (string) ($member->phone ?? '')) {
            $this->phone = (string) ($member->phone ?? '');
        }
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.members.profile-edit');
    }

    protected function hydrateRegenciesAndDistricts(): void
    {
        $this->regencies = $this->getRegencies($this->province);
        $this->districts = $this->getDistricts($this->province, $this->regency);
    }

    protected function getRegencies(string $province): array
    {
        if (trim($province) === '') {
            return [];
        }

        return cache()->remember('member_regions.regencies.'.md5($province), 3600, function () use ($province): array {
            return MemberRegion::query()
                ->select('regency')
                ->where('province', $province)
                ->whereNotNull('regency')
                ->where('regency', '!=', '')
                ->distinct()
                ->orderBy('regency')
                ->pluck('regency')
                ->map(fn ($r) => (string) $r)
                ->toArray();
        });
    }

    protected function getDistricts(string $province, string $regency): array
    {
        if (trim($province) === '' || trim($regency) === '') {
            return [];
        }

        return cache()->remember('member_regions.districts.'.md5($province.'|'.$regency), 3600, function () use ($province, $regency): array {
            return MemberRegion::query()
                ->select('district')
                ->where('province', $province)
                ->where('regency', $regency)
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->orderBy('district')
                ->pluck('district')
                ->map(fn ($d) => (string) $d)
                ->toArray();
        });
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $p = preg_replace('/\\D+/', '', $phone);
        if (str_starts_with($p, '0')) {
            return '62'.substr($p, 1);
        }
        if (! str_starts_with($p, '62')) {
            return '62'.$p;
        }

        return $p;
    }
}
