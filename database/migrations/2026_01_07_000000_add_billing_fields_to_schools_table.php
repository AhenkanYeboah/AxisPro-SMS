<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Which plan this school is on (matches keys in config/saas.php 'plans').
            // Null while on trial / before ever subscribing.
            $table->string('plan')->nullable()->after('status');

            // When the current paid subscription period ends. A school with
            // subscription_ends_at in the past (and no active trial) is locked out.
            $table->timestamp('subscription_ends_at')->nullable()->after('plan');

            // Paystack's reference for the customer, so we can look up / reuse
            // their card on file for recurring charges rather than re-collecting.
            $table->string('paystack_customer_code')->nullable()->after('subscription_ends_at');

            // Paystack's reference for an active subscription (if using
            // Paystack's own recurring billing rather than manual renewal).
            $table->string('paystack_subscription_code')->nullable()->after('paystack_customer_code');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'plan',
                'subscription_ends_at',
                'paystack_customer_code',
                'paystack_subscription_code',
            ]);
        });
    }
};
