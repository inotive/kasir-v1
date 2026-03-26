<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Mail\MemberVerificationMail;
use App\Models\DiningTable;
use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class VerifyEmailPage extends Component
{
    public ?int $memberId = null;

    public ?string $email = null;

    public ?string $notice = null;

    public function mount(): void
    {
        $this->memberId = is_numeric(session('pending_member_id')) ? (int) session('pending_member_id') : null;
        $this->email = is_string(session('pending_member_email')) ? (string) session('pending_member_email') : null;

        if (! $this->memberId && (! is_string($this->email) || trim($this->email) === '')) {
            $this->redirectRoute('self-order.start', navigate: true);
        }
    }

    public function resend(): void
    {
        $member = null;

        if ($this->memberId) {
            $member = Member::query()->whereKey($this->memberId)->first();
        }

        if (! $member && is_string($this->email) && trim($this->email) !== '') {
            $member = Member::query()->where('email', trim($this->email))->first();
        }

        if (! $member) {
            $this->notice = 'Jika email terdaftar, email verifikasi sudah dikirim. Silakan cek inbox/spam.';

            return;
        }

        if (! empty($member->email_verified_at)) {
            $this->notice = 'Jika email terdaftar, email verifikasi sudah dikirim. Silakan cek inbox/spam.';

            return;
        }

        $email = (string) $member->email;
        if ($email === '') {
            $this->notice = 'Email member tidak valid.';

            return;
        }

        $key = 'self-order:member-verify-resend:'.sha1($email.'|'.request()->ip());
        if (! RateLimiter::attempt($key, 1, fn () => true, 30)) {
            $this->notice = 'Terlalu sering kirim ulang. Coba lagi dalam beberapa saat.';

            return;
        }

        $member->verification_token = Str::random(40);
        $member->save();

        $tableId = session('dining_table_id');
        $tableCode = $tableId ? (string) optional(DiningTable::find($tableId))->qr_value : '';
        $query = $tableCode !== '' ? ['t' => $tableCode] : [];

        Mail::to($email)->queue(new MemberVerificationMail($member, $query));

        session([
            'pending_member_id' => (int) $member->id,
            'pending_member_email' => $email,
        ]);

        $this->memberId = (int) $member->id;
        $this->email = $email;
        $this->notice = 'Email verifikasi telah dikirim ulang. Silakan cek inbox/spam.';
    }

    public function continueAsGuest(): void
    {
        $this->redirectRoute('self-order.start', navigate: true);
    }

    #[Layout('layouts.self-order')]
    public function render(): View
    {
        return view('livewire.self-order.members.verify-email');
    }
}
