<?php

namespace App\Http\Controllers;

use App\Models\{Restaurant, SubscriptionPlan, Payment, Subscription, Invoice, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Step 1: Owner & Restaurant Signup Form
     * GET /register or /get-started
     */
    public function step1Form()
    {
        return view('onboarding.step1-signup');
    }

    /**
     * Step 1: Submit Owner & Restaurant Details
     * POST /register
     */
    public function step1Submit(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'owner_name'      => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'whatsapp_number' => 'required|string|max:30|unique:restaurants,whatsapp_number',
            'owner_phone'     => 'required|string|max:30',
            'owner_password'  => 'required|string|min:6',
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
        ]);

        $restaurant = new Restaurant($request->only([
            'name', 'owner_name', 'email', 'whatsapp_number', 'owner_phone', 'city', 'address'
        ]));

        $restaurant->status              = 'pending';
        $restaurant->registration_status = 'pending_plan';
        $restaurant->payment_status      = 'pending';
        $restaurant->is_active           = false;
        $restaurant->is_open             = false;
        $restaurant->bot_status          = 'disconnected';
        $restaurant->owner_password      = Hash::make($request->input('owner_password'));
        $restaurant->api_key             = 'sk_live_' . Str::random(32);
        $restaurant->features            = [
            'order_tracking'         => true,
            'customer_notifications' => true,
            'ai_suggestions'         => true,
            'human_handover'         => true,
            'voice_notes'            => true,
            'deal_broadcast'         => true,
            'rider_live_gps'         => true,
        ];
        $restaurant->save();

        AuditLog::log('onboarding.signup', "New restaurant signup initiated: {$restaurant->name} (#{$restaurant->id}) by {$restaurant->owner_name}");

        session(['onboarding_restaurant_id' => $restaurant->id]);

        return redirect()->route('onboarding.plan', $restaurant->id)
            ->with('success', "Account created! Please choose your preferred subscription plan.");
    }

    /**
     * Step 2: Choose Subscription Plan
     * GET /register/plan/{id}
     */
    public function step2PlanForm($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        // Seed standard plans if database table is empty
        if ($plans->isEmpty()) {
            $plans = collect([
                SubscriptionPlan::create([
                    'name'                 => 'Starter',
                    'slug'                 => 'starter',
                    'price_monthly'        => 3000,
                    'price_yearly'         => 30000,
                    'max_orders_per_month' => 500,
                    'max_menu_items'       => 40,
                    'is_active'            => true,
                    'is_popular'           => false,
                    'features'             => ['AI WhatsApp Bot', 'Order Tracking', 'Customer History', 'CSV Exports'],
                ]),
                SubscriptionPlan::create([
                    'name'                 => 'Pro',
                    'slug'                 => 'pro',
                    'price_monthly'        => 7000,
                    'price_yearly'         => 70000,
                    'max_orders_per_month' => 2000,
                    'max_menu_items'       => 150,
                    'is_active'            => true,
                    'is_popular'           => true,
                    'features'             => ['Everything in Starter', 'Live Rider GPS Tracking', 'Excel Menu OCR Import', 'Deal Broadcasts', 'Priority AI Response'],
                ]),
                SubscriptionPlan::create([
                    'name'                 => 'Enterprise',
                    'slug'                 => 'enterprise',
                    'price_monthly'        => 15000,
                    'price_yearly'         => 150000,
                    'max_orders_per_month' => 10000,
                    'max_menu_items'       => 500,
                    'is_active'            => true,
                    'is_popular'           => false,
                    'features'             => ['Everything in Pro', 'Unlimited Orders', 'Dedicated WhatsApp Server', 'Custom Google Sheet Webhooks', '24/7 SLA Support'],
                ]),
            ]);
        }

        return view('onboarding.step2-plan', compact('restaurant', 'plans'));
    }

    /**
     * Step 2: Submit Plan Selection
     * POST /register/plan/{id}
     */
    public function step2PlanSubmit(Request $request, $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_interval' => 'nullable|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        $restaurant->plan_id             = $plan->id;
        $restaurant->plan                = $plan->slug;
        $restaurant->registration_status = 'pending_payment';
        $restaurant->save();

        session(['billing_interval' => $request->input('billing_interval', 'monthly')]);

        return redirect()->route('onboarding.payment', $restaurant->id)
            ->with('success', "Plan '{$plan->name}' selected. Complete payment to submit for review.");
    }

    /**
     * Step 3: Payment Checkout
     * GET /register/payment/{id}
     */
    public function step3PaymentForm($id)
    {
        $restaurant = Restaurant::with('subscriptionPlan')->findOrFail($id);

        // If restaurant is already approved, skip payment and go directly to status / dashboard
        if ($restaurant->status === 'active' || $restaurant->registration_status === 'approved') {
            return redirect()->route('onboarding.status', $restaurant->id);
        }

        $plan = $restaurant->subscriptionPlan ?: SubscriptionPlan::first();
        $interval = session('billing_interval', 'monthly');
        $amount = ($interval === 'yearly' && $plan->price_yearly > 0) ? (float) $plan->price_yearly : (float) $plan->price_monthly;

        return view('onboarding.step3-payment', compact('restaurant', 'plan', 'interval', 'amount'));
    }

    /**
     * Step 3: Submit Payment Verification
     * POST /register/payment/{id}
     */
    public function step3PaymentSubmit(Request $request, $id)
    {
        $restaurant = Restaurant::with('subscriptionPlan')->findOrFail($id);
        $plan = $restaurant->subscriptionPlan ?: SubscriptionPlan::first();

        // Support both field name conventions from HTML form
        $method = $request->input('payment_method') ?: $request->input('payment_gateway');
        $ref    = $request->input('payment_reference') ?: $request->input('transaction_ref');

        $request->merge([
            'payment_method'    => $method,
            'payment_reference' => $ref,
        ]);

        $request->validate([
            'payment_method'    => 'required|string|in:stripe,jazzcash,easypaisa,bank_transfer',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        $interval = session('billing_interval', 'monthly');
        $amount = ($interval === 'yearly' && $plan->price_yearly > 0) ? (float) $plan->price_yearly : (float) $plan->price_monthly;

        $reference = $request->input('payment_reference') ?: ('PAY-' . strtoupper(Str::random(10)));
        $stripeId  = $request->payment_method === 'stripe' ? ('ch_' . Str::random(24)) : null;

        // 1. Record Payment
        $payment = Payment::create([
            'restaurant_id'     => $restaurant->id,
            'plan_id'           => $plan->id,
            'amount'            => $amount,
            'currency'          => 'PKR',
            'payment_method'    => $request->payment_method,
            'payment_reference' => $reference,
            'stripe_payment_id' => $stripeId,
            'status'            => 'completed',
            'completed_at'      => now(),
        ]);

        // 2. Generate Invoice
        Invoice::create([
            'invoice_number'    => 'INV-' . date('Y') . '-' . str_pad($restaurant->id * 10 + random_int(1, 9), 5, '0', STR_PAD_LEFT),
            'restaurant_id'     => $restaurant->id,
            'plan_name'         => $plan->name . ' (' . ucfirst($interval) . ')',
            'amount'            => $amount,
            'currency'          => 'PKR',
            'payment_method'    => ucfirst(str_replace('_', ' ', $request->payment_method)),
            'payment_reference' => $reference,
            'status'            => 'paid',
            'paid_at'           => now(),
            'due_date'          => now()->toDateString(),
            'notes'             => "Onboarding subscription payment via {$request->payment_method}",
        ]);

        // 3. Update Restaurant to Pending Review
        $restaurant->payment_status      = 'completed';
        $restaurant->payment_id          = $payment->id;
        $restaurant->registration_status = 'pending_review';
        $restaurant->status              = 'pending';
        $restaurant->save();

        AuditLog::log('onboarding.payment_completed', "Payment of Rs. {$amount} ({$request->payment_method}) completed by {$restaurant->name} (#{$restaurant->id}) for {$plan->name}. Ready for SuperAdmin review.");

        return redirect()->route('onboarding.status', $restaurant->id)
            ->with('success', '🎉 Payment verified! Your registration has been submitted to Super Admin for verification.');
    }

    /**
     * Live Application Status Screen
     * GET /register/status/{id}
     */
    public function statusPage($id)
    {
        $restaurant = Restaurant::with(['subscriptionPlan', 'payments'])->findOrFail($id);

        // If approved by Super Admin, automatically authenticate owner session
        if ($restaurant->status === 'active' || $restaurant->registration_status === 'approved') {
            session([
                "restaurant_{$restaurant->id}" => true,
                "restaurant_{$restaurant->id}_login_time" => now()->toIso8601String(),
            ]);
        }

        return view('onboarding.status', compact('restaurant'));
    }
}
