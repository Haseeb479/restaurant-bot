<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Support\BotEvolutionClient;
use Illuminate\Console\Command;

class EvolutionInitCommand extends Command
{
    protected $signature = 'evolution:init {id? : Restaurant ID (optional, default all active)}';
    protected $description = 'Initialize and configure EvolutionAPI instances and webhooks for restaurants';

    public function handle(): int
    {
        $id = $this->argument('id');
        $query = Restaurant::query();

        if ($id) {
            $query->where('id', $id);
        } else {
            // ── GAP 7: Use the boolean is_active column, not status string ────
            // Some restaurants may have is_active=1 but status != 'active' (e.g.
            // 'pending' or other lifecycle values). The boolean flag is the
            // definitive "this restaurant should have a working bot" signal.
            $query->where('is_active', true);
        }

        $restaurants = $query->get();

        if ($restaurants->isEmpty()) {
            $this->warn('No matching restaurants found.');
            return 0;
        }

        foreach ($restaurants as $r) {
            $this->info("Setting up Evolution instance for: {$r->name} (#{$r->id})...");

            $res = BotEvolutionClient::createInstance($r);
            $instanceName = BotEvolutionClient::instanceName($r);

            $this->line("  Instance: {$instanceName}");
            $this->line("  Response: " . json_encode($res));

            $hookOk = BotEvolutionClient::configureWebhook($r);
            $this->line("  Webhook: " . ($hookOk ? "✅ CONFIGURED" : "❌ FAILED"));
        }

        $this->info('Done!');
        return 0;
    }
}
