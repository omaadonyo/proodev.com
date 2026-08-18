<?php

namespace App\Services;

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\CreditTransactionType;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VerificationStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserVerification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    /**
     * Create a pending $8 developer verification payment.
     */
    public function createVerificationPayment(User $user, ?string $shortName = null): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'purpose' => PaymentPurpose::Verification,
            'amount' => (float) config('billing.developer.verification.price', 8),
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['short_name' => $shortName],
        ]);
    }

    /**
     * Create a pending credit bundle payment.
     */
    public function createCreditPayment(User $user, int $bundleIndex): Payment
    {
        $bundles = (array) config('billing.developer.credits.bundles', []);
        $bundle = $bundles[$bundleIndex] ?? null;

        if (! $bundle) {
            throw new InvalidArgumentException('Unknown credit bundle.');
        }

        return Payment::create([
            'user_id' => $user->id,
            'purpose' => PaymentPurpose::Credits,
            'amount' => (float) $bundle['price'],
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['credits' => (int) $bundle['credits']],
        ]);
    }

    /**
     * Create a pending $8 developer repo auto-scan subscription payment.
     */
    public function createAutoScanPayment(User $user): Payment
    {
        $interval = (int) config('billing.developer.auto_scan.interval_days', 30);

        return Payment::create([
            'user_id' => $user->id,
            'purpose' => PaymentPurpose::AutoScan,
            'amount' => (float) config('billing.developer.auto_scan.price', 8),
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['interval_days' => $interval],
        ]);
    }

    /**
     * Create a pending company subscription payment.
     */
    public function createSubscriptionPayment(Company $company, CompanyPlan $plan): Payment
    {
        $firstMonth = $plan->firstMonthPrice();

        $amount = $firstMonth !== null
            ? (float) $firstMonth
            : (float) $plan->monthlyPrice();

        return Payment::create([
            'user_id' => $company->owner_id,
            'company_id' => $company->id,
            'purpose' => PaymentPurpose::Subscription,
            'amount' => $amount,
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['plan' => $plan->value, 'first_month' => $firstMonth !== null],
        ]);
    }

    /**
     * Create a pending job post credits payment from a bundle.
     */
    public function createJobPostsPayment(Company $company, int $posts): Payment
    {
        $bundles = (array) config('billing.companies.job_posts.bundles', []);
        $bundle = collect($bundles)->firstWhere('posts', $posts);

        if (! $bundle) {
            throw new InvalidArgumentException('Unknown job posts bundle.');
        }

        return Payment::create([
            'user_id' => $company->owner_id,
            'company_id' => $company->id,
            'purpose' => PaymentPurpose::JobPosts,
            'amount' => (float) $bundle['price'],
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['job_posts' => (int) $bundle['posts']],
        ]);
    }

    /**
     * Create a pending $299 company verification payment.
     */
    public function createCompanyVerificationPayment(Company $company): Payment
    {
        return Payment::create([
            'user_id' => $company->owner_id,
            'company_id' => $company->id,
            'purpose' => PaymentPurpose::Verification,
            'amount' => (float) config('billing.companies.verification.price', 299),
            'currency' => (string) config('billing.currency', 'USD'),
            'status' => PaymentStatus::Pending,
            'provider' => 'manual',
            'metadata' => ['company_verification' => true],
        ]);
    }

    /**
     * Mark a payment as paid and fulfill whatever it was for.
     */
    public function markPaid(Payment $payment, ?User $admin = null): void
    {
        $wasPaid = DB::transaction(function () use ($payment, $admin) {
            if ($payment->status === PaymentStatus::Paid) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatus::Paid,
                'confirmed_by' => $admin?->id,
                'paid_at' => now(),
            ]);

            match ($payment->purpose) {
                PaymentPurpose::Verification => $this->fulfillVerification($payment),
                PaymentPurpose::Credits => $this->fulfillCredits($payment),
                PaymentPurpose::Subscription => $this->fulfillSubscription($payment),
                PaymentPurpose::AutoScan => $this->fulfillAutoScan($payment),
                PaymentPurpose::JobPosts => $this->fulfillJobPosts($payment),
            };

            return true;
        });

        if ($wasPaid) {
            app(NotificationService::class)->paymentConfirmed($payment);
        }
    }

    private function fulfillVerification(Payment $payment): void
    {
        if (($payment->metadata['company_verification'] ?? false) && $payment->company) {
            $payment->company->update([
                'status' => CompanyStatus::Approved,
                'approved_at' => $payment->company->approved_at ?? now(),
            ]);

            return;
        }

        $user = $payment->user;
        $shortName = $payment->metadata['short_name'] ?? $user->handle();

        UserVerification::create([
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'short_name' => $shortName,
            'status' => VerificationStatus::Approved,
            'approved_at' => now(),
        ]);

        $user->update([
            'is_verified' => true,
            'verified_at' => now(),
            'short_domain' => $shortName,
        ]);
    }

    private function fulfillCredits(Payment $payment): void
    {
        $credits = (int) ($payment->metadata['credits'] ?? 0);

        if ($credits > 0) {
            app(CreditService::class)->grant(
                $payment->user,
                $credits,
                CreditTransactionType::Purchase,
                'Purchased '.$credits.' credits.',
                $payment,
            );
        }
    }

    private function fulfillAutoScan(Payment $payment): void
    {
        $user = $payment->user;

        if (! $user) {
            return;
        }

        $interval = (int) ($payment->metadata['interval_days'] ?? config('billing.developer.auto_scan.interval_days', 30));

        $user->update([
            'auto_scan_enabled' => true,
            'auto_scan_active_until' => now()->addDays($interval),
        ]);
    }

    private function fulfillJobPosts(Payment $payment): void
    {
        $company = $payment->company;

        if (! $company) {
            return;
        }

        $company->grantJobPosts((int) ($payment->metadata['job_posts'] ?? 0));
    }

    private function fulfillSubscription(Payment $payment): void
    {
        $company = $payment->company;
        $plan = CompanyPlan::tryFrom((string) ($payment->metadata['plan'] ?? ''));

        if (! $company || ! $plan) {
            return;
        }

        $company->update([
            'plan' => $plan,
            'status' => CompanyStatus::Approved,
            'approved_at' => $company->approved_at ?? now(),
            'plan_renews_at' => now()->addMonth(),
        ]);
    }
}
