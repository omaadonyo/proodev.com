<?php

namespace App\Services;

use App\Mail\ApplicationConfirmationMail;
use App\Mail\NewApplicationMail;
use App\Mail\NewJobPostedMail;
use App\Mail\NewUserRegisteredMail;
use App\Mail\PaymentAwaitingConfirmationMail;
use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\PayoutNotificationMail;
use App\Mail\VerificationApprovedMail;
use App\Mail\VouchReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Vouch;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PaymentAwaitingConfirmationNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\WelcomeNotification;
use App\Services\Recruiter\JobMatchService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Sends email + database notifications for registration and payment events.
 */
class NotificationService
{
    /**
     * Handle a freshly registered user: welcome email + notification to the
     * user, plus an admin alert (email + database notification).
     */
    public function newRegistration(User $user): void
    {
        try {
            Mail::to($user)->sendNow(new WelcomeMail($user));
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $user->notifyNow(new WelcomeNotification($user));
        } catch (\Throwable $e) {
            report($e);
        }

        foreach (static::admins() as $admin) {
            try {
                Mail::to($admin)->sendNow(new NewUserRegisteredMail($user));
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $admin->notifyNow(new NewUserRegisteredNotification($user));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Handle a confirmed payment: send invoice/receipt to the customer. Admins
     * get a payout notice for manual methods (WorldRemit / bank transfer) or an
     * invoice copy for gateway payments, plus a database notification.
     */
    public function paymentConfirmed(Payment $payment): void
    {
        $recipient = $payment->user ?? $payment->company?->owner;

        if ($recipient) {
            try {
                Mail::to($recipient)->send(new PaymentInvoiceMail($payment));
            } catch (\Throwable $e) {
                report($e);
            }
            $payment->forceFill(['invoice_emailed_at' => now()])->save();
        }

        $manual = $payment->payment_method?->isManual() ?? false;

        foreach (static::admins() as $admin) {
            try {
                if ($manual) {
                    Mail::to($admin)->send(new PayoutNotificationMail($payment));
                } else {
                    Mail::to($admin)->send(new PaymentInvoiceMail($payment, copy: true));
                }
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $admin->notify(new PaymentReceivedNotification($payment));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * A customer submitted a manual payment (WorldRemit / bank transfer) and
     * says the funds are on the way. Alert every admin by email and database
     * notification so the payment can be verified and confirmed.
     */
    public function paymentSubmittedByCustomer(Payment $payment): void
    {
        $recipient = $payment->user ?? $payment->company?->owner;

        if ($recipient && $recipient->wantsEmail('transactions')) {
            try {
                Mail::to($recipient)->send(new PaymentReceivedMail($payment));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        foreach (static::admins() as $admin) {
            try {
                Mail::to($admin)->send(new PaymentAwaitingConfirmationMail($payment));
            } catch (\Throwable $e) {
                report($e);
            }
            try {
                $admin->notify(new PaymentAwaitingConfirmationNotification($payment));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * A verification request was approved: email the developer plus an admin
     * copy so the team can track verification volume.
     */
    public function verificationApproved(VerificationRequest $request): void
    {
        $request->loadMissing('user');

        try {
            Mail::to($request->user)->send(new VerificationApprovedMail($request));
        } catch (\Throwable $e) {
            report($e);
        }

        foreach (static::admins() as $admin) {
            try {
                Mail::to($admin)->send(new VerificationApprovedMail($request, copy: true));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * A developer received a vouch: email them so they can see and share it.
     */
    public function vouchReceived(Vouch $vouch): void
    {
        $vouch->loadMissing(['voucher', 'vouchee', 'skill']);

        try {
            Mail::to($vouch->vouchee)->send(new VouchReceivedMail($vouch));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * A candidate applied to a job: confirm to the applicant, alert the
     * hiring company, and copy the platform admins.
     */
    public function jobApplicationSubmitted(Application $application): void
    {
        $application->loadMissing(['user', 'job.company']);

        try {
            Mail::to($application->user)->send(new ApplicationConfirmationMail($application));
        } catch (\Throwable $e) {
            report($e);
        }

        $company = $application->job->company;
        $companyOwner = $company instanceof Company ? $company->owner : null;

        if ($companyOwner instanceof User && $companyOwner->id !== $application->user_id) {
            try {
                Mail::to($companyOwner)->send(new NewApplicationMail($application));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        foreach (static::admins() as $admin) {
            try {
                Mail::to($admin)->send(new NewApplicationMail($application, copy: true));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * A new job was published: email developers whose skills match the job
     * description and who opted in to new-job-offer emails. Matching reuses
     * the recruiter job-match keyword extraction, so only relevant devs hear
     * about the role.
     */
    public function jobPublished(Job $job): void
    {
        $job->loadMissing('company');

        app(JobMatchService::class)
            ->matchingDevelopersFor($job)
            ->each(function (User $user) use ($job) {
                if ($user->wantsEmail('job_offers')) {
                    try {
                        Mail::to($user)->send(new NewJobPostedMail($job));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
    }

    /**
     * @return Collection<int, User>
     */
    public static function admins()
    {
        return User::where('is_admin', true)->get();
    }
}
