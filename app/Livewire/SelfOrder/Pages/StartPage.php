<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Mail\MemberVerificationMail;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\MemberRegion;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class StartPage extends Component
{
    public $setting;

    public $tableNumber;

    public string $tab = 'guest';

    public ?string $notice = null;

    public string $guest_name = '';

    public string $guest_phone = '';

    public string $member_identifier = '';

    public string $register_name = '';

    public string $register_email = '';

    public string $register_phone = '';

    public string $register_province = '';

    public string $register_regency = '';

    public string $register_district = '';

    public array $provinces = [];

    public array $regencies = [];

    public array $districts = [];

    public function mount(): void
    {
        $this->setting = Setting::current();
        $tableId = session('dining_table_id');
        $this->tableNumber = $tableId ? optional(DiningTable::find($tableId))->table_number : null;

        $this->guest_name = (string) (session('name') ?? '');
        $this->guest_phone = (string) (session('phone') ?? '');
        $this->member_identifier = (string) (session('email') ?? '');

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
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['guest', 'member', 'register'], true)) {
            return;
        }

        $this->resetErrorBag();
        $this->notice = null;
        $this->tab = $tab;
    }

    public function proceedGuest(): void
    {
        $validated = $this->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['required', 'string', 'max:30'],
        ]);

        session([
            'customer_ready' => true,
            'customer_type' => 'guest',
            'member_id' => null,
            'name' => $validated['guest_name'],
            'phone' => $this->normalizePhone($validated['guest_phone']),
            'email' => null,
        ]);

        $this->redirectRoute('self-order.home', navigate: true);
    }

    public function proceedMember(): void
    {
        $validated = $this->validate([
            'member_identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($validated['member_identifier']);

        $member = null;
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $member = Member::query()->where('email', $identifier)->first();
        } else {
            $member = Member::query()->where('phone', $this->normalizePhone($identifier))->first();
        }

        if (! $member) {
            $this->addError('member_identifier', 'Member tidak ditemukan. Silakan daftar terlebih dahulu.');

            return;
        }

        if (empty($member->email_verified_at)) {
            $this->addError('member_identifier', 'Email member belum diverifikasi. Silakan cek email untuk verifikasi terlebih dahulu.');

            return;
        }

        session([
            'customer_ready' => true,
            'customer_type' => 'member',
            'member_id' => (int) $member->id,
            'name' => (string) $member->name,
            'phone' => (string) ($member->phone ?? ''),
            'email' => (string) ($member->email ?? ''),
        ]);

        $this->redirectRoute('self-order.home', navigate: true);
    }

    public function updatedRegisterProvince($value): void
    {
        $this->register_province = (string) $value;
        $this->register_regency = '';
        $this->register_district = '';
        $this->districts = [];

        $province = $this->register_province;
        $this->regencies = cache()->remember('member_regions.regencies.'.md5($province), 3600, function () use ($province): array {
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

    public function updatedRegisterRegency($value): void
    {
        $this->register_regency = (string) $value;
        $this->register_district = '';

        $province = $this->register_province;
        $regency = $this->register_regency;
        $this->districts = cache()->remember('member_regions.districts.'.md5($province.'|'.$regency), 3600, function () use ($province, $regency): array {
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

    public function registerMember(): void
    {
        $validated = $this->validate([
            'register_name' => ['required', 'string', 'max:255'],
            'register_email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'register_phone' => ['required', 'string', 'max:30', 'unique:members,phone'],
            'register_province' => ['required', 'string', 'max:255'],
            'register_regency' => ['required', 'string', 'max:255'],
            'register_district' => ['required', 'string', 'max:255'],
        ]);

        $region = MemberRegion::query()
            ->where('province', $validated['register_province'])
            ->where('regency', $validated['register_regency'])
            ->where('district', $validated['register_district'])
            ->first();

        if (! $region) {
            $this->addError('register_district', 'Wilayah tidak valid. Silakan pilih ulang.');

            return;
        }

        $member = Member::create([
            'name' => $validated['register_name'],
            'email' => $validated['register_email'],
            'phone' => $this->normalizePhone($validated['register_phone']),
            'member_region_id' => (int) $region->id,
            'points' => 0,
            'verification_token' => Str::random(40),
            'email_verified_at' => null,
        ]);

        $tableId = session('dining_table_id');
        $tableCode = $tableId ? (string) optional(DiningTable::find($tableId))->qr_value : '';
        $query = $tableCode !== '' ? ['t' => $tableCode] : [];

        Mail::to($member->email)->queue(new MemberVerificationMail($member, $query));

        session([
            'pending_member_id' => (int) $member->id,
            'pending_member_email' => (string) $member->email,
        ]);

        $this->redirectRoute('self-order.member.verify-email', navigate: true);
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

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.start');
    }
}
