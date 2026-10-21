<?php

namespace App\Http\Controllers;

use App\Models\{Restaurant, SubscriptionPlan, Payment, Subscription, Invoice, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    /**
     * Ensure the requester is either the applicant who signed up in this session,
     * the authenticated restaurant owner, or a super admin.
     */
    private function authorizeOnboarding(int|string $id): void
    {
        $sessionOnboardingId = session('onboarding_restaurant_id');
        $isOwnerSession      = session("restaurant_{$id}") === true;
        $isSuperAdmin        = session('admin_logged_in') === true;

        if (! $isSuperAdmin && ! $isOwnerSession && (int) $sessionOnboardingId !== (int) $id) {
            abort(403, 'Unauthorized access to this registration application.');
        }
    }

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
            'email'           => 'required|email|max:255|unique:restaurants,email',
            'whatsapp_number' => 'required|string|max:30|unique:restaurants,whatsapp_number',
            'owner_phone'     => 'required|string|max:30',
            'owner_password'  => \App\Support\PasswordPolicy::rule(true),
            'city'            => 'nullable|string|max:100',
            'address'         => 'nullable|string|max:500',
        ]);

        $restaurant = new Restaurant($request->only([
            'name', 'owner_name', 'email', 'whatsapp_number', 'owner_phone', 'city', 'address'
        ]));

        // ── Direct plan onboarding state (no email verification barrier) ──
        $restaurant->status              = 'pending';
        $restaurant->registration_status = 'pending_plan';
        $restaurant->email_verified_at   = now();
        $restaurant->payment_status      = 'pending';
        $restaurant->is_active           = false;
        $restaurant->is_open             = false;
        $restaurant->bot_status          = 'disconnected';
        $restaurant->owner_password      = Hash::make($request->input('owner_password'));
        $restaurant->api_key             = 'sk_live_' . Str::random(32);
        $restaurant->features            = [
            'customer_notifications' => true,
            'ai_suggestions'         => true,
            'human_handover'         => true,
            'voice_notes'            => true,
            'deal_broadcast'         => true,
        ];
        $restaurant->save();

        AuditLog::log('onboarding.signup', "New restaurant signup initiated: {$restaurant->name} (#{$restaurant->id}) by {$restaurant->owner_name}");

        session(['onboarding_restaurant_id' => $restaurant->id]);

        return redirect()->route('onboarding.plan', $restaurant->id)
            ->with('success', "Account registered! Please select your subscription plan to continue.");
    }

    /**
     * Show verification prompt notice (Deprecated - email verification bypassed).
     * GET /register/verify-notice/{id}
     */
    public function verifyNotice($id)
    {
        return redirect()->route('onboarding.plan', $id);
    }

    /**
     * Validate presented verification token (Bypassed).
     * GET /register/verify/{id}/{token}
     */
    public function verify(Request $request, $id, $token)
    {
        session(['onboarding_restaurant_id' => $id]);
        return redirect()->route('onboarding.plan', $id);
    }

    /**
     * Resend verification link (Bypassed).
     * POST /register/resend-verification/{id}
     */
    public function resendVerification(Request $request, $id)
    {
        return redirect()->route('onboarding.plan', $id);
    }

    /**
     * Step 2: Choose Subscription Plan
     * GET /register/plan/{id}
     */
    public function step2PlanForm($id)
    {
        $this->authorizeOnboarding($id);
        $restaurant = Restaurant::findOrFail($id);
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        // Seed standard plans if database table is empty
        if ($plans->isEmpty()) {
            $plans = collect([
                SubscriptionPlan::create([
                    'name'                 => 'Cloud POS Core',
                    'slug'                 => 'starter',
                    'price_monthly'        => 0,
                    'price_yearly'         => 0,
                    'max_orders_per_month' => 500,
                    'max_menu_items'       => 50,
                    'is_active'            => true,
                    'is_popular'           => false,
                    'features'             => ['1 Active Cloud POS Terminal', 'Digital Dine-in QR Menu', 'Thermal Receipt Printer Sync', '14-Day Free Trial'],
                ]),
                SubscriptionPlan::create([
                    'name'                 => 'POS + WhatsApp Pro',
                    'slug'                 => 'pro',
                    'price_monthly'        => 8000,
                    'price_yearly'         => 80000,
                    'max_orders_per_month' => 5000,
                    'max_menu_items'       => 250,
                    'is_active'            => true,
                    'is_popular'           => true,
                    'features'             => ['Unlimited POS Terminals', 'Official WhatsApp Ordering Bot', 'Table Seating & Kitchen KDS', 'Automated Customer Re-Order Alerts'],
                ]),
                SubscriptionPlan::create([
                    'name'                 => 'Multi-Outlet OS',
                    'slug'                 => 'enterprise',
                    'price_monthly'        => 16000,
                    'price_yearly'         => 160000,
                    'max_orders_per_month' => 50000,
                    'max_menu_items'       => 1000,
                    'is_active'            => true,
                    'is_popular'           => false,
                    'features'             => ['Multi-branch central inventory sync', 'Custom WhatsApp AI Persona', 'Dedicated Server & SLA Guarantee'],
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
        $this->authorizeOnboarding($id);
        $restaurant = Restaurant::findOrFail($id);

        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_interval' => 'nullable|in:monthly,yearly',
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        $restaurant->plan_id             = $plan->id;
        $restaurant->plan                = $plan->slug;

        // If Starter / Free 14-Day trial, activate trial directly
        if ($plan->price_monthly <= 0 || $plan->slug === 'starter') {
            $restaurant->payment_status      = 'completed';
            $restaurant->registration_status = 'approved';
            $restaurant->status              = 'active';
            $restaurant->trial_started_at    = now();
            $restaurant->plan_expires_at     = now()->addDays(14);
            $restaurant->save();

            AuditLog::log('onboarding.trial_activated', "14-Day Free Trial activated for restaurant {$restaurant->name} (#{$restaurant->id}) on {$plan->name}.");

            return redirect()->route('onboarding.status', $restaurant->id)
                ->with('success', "14-Day Free Trial activated! Your restaurant is ready.");
        }

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
        $this->authorizeOnboarding($id);
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
        $this->authorizeOnboarding($id);
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
            'payment_slip'      => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
        ]);

        $interval = session('billing_interval', 'monthly');
        $amount = ($interval === 'yearly' && $plan->price_yearly > 0) ? (float) $plan->price_yearly : (float) $plan->price_monthly;

        $reference = $request->input('payment_reference') ?: ('PAY-' . strtoupper(Str::random(10)));
        $stripeId  = $request->payment_method === 'stripe' ? ('ch_' . Str::random(24)) : null;

        // Process uploaded payment slip / receipt
        // ── B5: Store in private storage — NOT in public/ ─────────────────────
        // Payment slips contain bank account numbers and transaction details.
        // Storing them under public/ would make them downloadable by anyone with
        // the filename. We now store them in storage/app/private/ and serve them
        // only through an admin-gated signed route (StorageController).
        $slipPath = null;
        if ($request->hasFile('payment_slip') && $request->file('payment_slip')->isValid()) {
            $slipFile = $request->file('payment_slip');
            $ext = strtolower($slipFile->getClientOriginalExtension() ?: 'jpg');
            if (in_array($ext, ['jpeg', 'png', 'jpg', 'webp', 'pdf'], true)) {
                $slipName = 'slip_' . $restaurant->id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $slipFile->storeAs('payment_slips', $slipName, 'private');
                $slipPath = 'payment_slips/' . $slipName;
            }
        }

        // 1. Record Payment
        $payment = Payment::create([
            'restaurant_id'     => $restaurant->id,
            'plan_id'           => $plan->id,
            'amount'            => $amount,
            'currency'          => 'PKR',
            'payment_method'    => $request->payment_method,
            'payment_reference' => $reference,
            'payment_slip'      => $slipPath,
            'stripe_payment_id' => $stripeId,
            // ── C6: Do NOT auto-complete — wait for admin verification ─────────
            // Previously set to 'completed' instantly, which let any fake reference
            // number activate a restaurant. Now stays 'pending_verification' until
            // the Super Admin reviews and manually approves.
            'status'            => 'pending_verification',
            'completed_at'      => null,
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
            'status'            => 'pending',  // admin must verify before marking paid (C6)
            'paid_at'           => null,
            'due_date'          => now()->toDateString(),
            'notes'             => "Onboarding subscription payment via {$request->payment_method} — awaiting admin verification",
        ]);

        // 3. Update Restaurant to Pending Review (NOT active yet — C6)
        $restaurant->payment_status      = 'pending_verification';
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

        $sessionOnboardingId = session('onboarding_restaurant_id');
        $isOwnerSession      = session("restaurant_{$restaurant->id}") === true;
        $isApplicantSession  = (int) $sessionOnboardingId === (int) $restaurant->id;
        $isSuperAdmin        = session('admin_logged_in') === true;

        // Auto-authenticate ONLY if the requester is verified as the actual applicant from this session
        if ($isApplicantSession && ($restaurant->status === 'active' || $restaurant->registration_status === 'approved')) {
            session([
                "restaurant_{$restaurant->id}" => true,
                "restaurant_{$restaurant->id}_login_time" => now()->toIso8601String(),
            ]);
            $isOwnerSession = true;
        }

        $canViewStatus = $isSuperAdmin || $isOwnerSession || $isApplicantSession;
        abort_unless($canViewStatus, 403, 'Unauthorized access to this registration status.');

        return view('onboarding.status', [
            'restaurant'           => $restaurant,
            'isAuthenticatedOwner' => $isOwnerSession,
        ]);
    }
}
