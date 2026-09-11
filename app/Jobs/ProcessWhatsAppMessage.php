<?php

namespace App\Jobs;

use App\Models\Restaurant;
use App\Services\WhatsAppAiBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Restaurant $restaurant,
        public string $customerPhone,
        public string $recipientJid,
        public string $text,
        public ?array $locationCoords = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppAiBotService $botService): void
    {
        try {
            $botService->handle(
                $this->restaurant,
                $this->customerPhone,
                $this->recipientJid,
                $this->text,
                $this->locationCoords
            );
        } catch (\Throwable $e) {
            Log::error("ProcessWhatsAppMessage Job Failed for {$this->restaurant->name}: " . $e->getMessage(), [
                'exception' => $e,
                'phone'     => $this->customerPhone,
            ]);
            throw $e;
        }
    }
}
