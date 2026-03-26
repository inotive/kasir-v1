<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\VoucherCampaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VoucherAlertCommand extends Command
{
    protected $signature = 'vouchers:alert';

    protected $description = 'Alert voucher yang mendekati expiry atau kuota menipis';

    public function handle(): int
    {
        $setting = Setting::current();
        $daysBeforeExpiry = max(0, (int) ($setting->voucher_alert_days_before_expiry ?? 7));
        $quotaThreshold = max(0, (int) ($setting->voucher_alert_quota_threshold ?? 10));

        $campaigns = VoucherCampaign::query()
            ->where('is_active', true)
            ->withSum('codes', 'times_redeemed')
            ->get();

        foreach ($campaigns as $c) {
            $used = (int) ($c->codes_sum_times_redeemed ?? 0);
            $limit = $c->usage_limit_total === null ? null : (int) $c->usage_limit_total;
            $remaining = $limit === null ? null : max(0, $limit - $used);

            if ($c->ends_at && $daysBeforeExpiry > 0 && $c->ends_at->lte(now()->addDays($daysBeforeExpiry))) {
                Log::warning('Voucher campaign expiring soon', [
                    'campaign_id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'ends_at' => (string) $c->ends_at,
                ]);
            }

            if ($remaining !== null && $quotaThreshold > 0 && $remaining <= $quotaThreshold) {
                Log::warning('Voucher campaign quota low', [
                    'campaign_id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'used' => $used,
                    'limit' => $limit,
                    'remaining' => $remaining,
                ]);
            }
        }

        $this->info('Voucher alert check selesai.');

        return self::SUCCESS;
    }
}
