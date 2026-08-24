<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // When set to a future timestamp, a human (owner/manager) has taken
            // over this conversation and the AI must stay muted until then. The
            // bot writes this on escalation and clears it on release; it also
            // rehydrates active handoffs from here on restart, so a bot reboot
            // mid-complaint does not hand the customer straight back to the AI.
            //
            // Deliberately NOT reusing the `state` column: `state` records the
            // last message intent (chat/order_confirmed) and is overwritten on
            // every logged reply, which would clobber a handoff flag.
            $table->timestamp('human_handling_until')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('human_handling_until');
        });
    }
};
