<?php

namespace App\Http\Controllers;

use App\Mail\MemberVerificationMail;
use App\Models\DiningTable;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:members,phone'],
            'member_region_id' => ['required', 'integer', 'exists:member_regions,id'],
        ]);

        $member = Member::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $this->normalizePhone($validated['phone']),
            'member_region_id' => (int) $validated['member_region_id'],
            'points' => 0,
            'verification_token' => Str::random(40),
            'email_verified_at' => null,
        ]);

        Mail::to($member->email)->queue(new MemberVerificationMail($member));

        return back()->with('success', 'Pendaftaran berhasil. Silakan cek email untuk verifikasi.');
    }

    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $member = Member::where('verification_token', $token)->first();
        if (! $member) {
            return view('livewire.self-order.members.verified', ['member' => null, 'error' => 'Tautan verifikasi tidak valid atau telah digunakan.']);
        }

        $member->email_verified_at = now();
        $member->verification_token = null;
        $member->save();

        $tableCode = trim((string) $request->query('t', ''));
        $hasTableContext = is_numeric(session('dining_table_id')) || $tableCode !== '';

        if ($hasTableContext) {
            if (! is_numeric(session('dining_table_id')) && $tableCode !== '') {
                $table = DiningTable::query()->where('qr_value', $tableCode)->first();
                if ($table) {
                    if (! is_array(session('cart_items'))) {
                        session(['cart_items' => []]);
                    }
                    if (! is_string(session('self_order_token')) || trim((string) session('self_order_token')) === '') {
                        session(['self_order_token' => Str::random(40)]);
                    }

                    session([
                        'dining_table_id' => (int) $table->id,
                        'payment_token' => null,
                        'external_id' => null,
                        'has_unpaid_transaction' => false,
                    ]);
                }
            }

            if (is_numeric(session('dining_table_id'))) {
                session([
                    'customer_ready' => true,
                    'customer_type' => 'member',
                    'member_id' => (int) $member->id,
                    'name' => (string) $member->name,
                    'phone' => (string) ($member->phone ?? ''),
                    'email' => (string) ($member->email ?? ''),
                ]);

                return redirect()->route('self-order.home');
            }
        }

        return view('livewire.self-order.members.verified', ['member' => $member, 'error' => null]);
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
