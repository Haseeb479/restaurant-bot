<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\AuditLog;
use Illuminate\Console\Command;

class CheckSubscriptionExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit all restaurant subscription plans, deactivate expired accounts, and flag renewal warnings.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting SaaS subscription expiration check...');

        $now = now();

        // 1. Process expired accounts
        $expiredRestaurants = Restaurant::where('is_active', true)
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', $now)
            ->where('plan', '!=', 'trial')
            ->get();

        $deactivatedCount = 0;
        foreach ($expiredRestaurants as $r) {
            $r->is_active = false;
            $r->deactivated_at = $now;
            $r->save();

            Subscription::where('restaurant_id', $r->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            AuditLog::log(
                'subscription.expired',
                "Subscription expired for {$r->name} (#{$r->id}) on {$r->plan_expires_at->format('Y-m-d')}. Account automatically deactivated."
            );

            $this->warn("Deactivated: {$r->name} (#{$r->id}) - Expired: {$r->plan_expires_at->toDateTimeString()}");
            $deactivatedCount++;
        }

        // 2. Identify restaurants expiring within 3 days for renewal alerts
        $expiringSoon = Restaurant::where('is_active', true)
            ->whereNotNull('plan_expires_at')
            ->whereBetween('plan_expires_at', [$now, $now->copy()->addDays(3)])
            ->get();

        $this->info("Expired accounts deactivated: {$deactivatedCount}");
        $this->info("Accounts expiring within 3 days: {$expiringSoon->count()}");

        return Command::SUCCESS;
    }
}
