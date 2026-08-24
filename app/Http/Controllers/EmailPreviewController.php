<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VerificationRequestType;
use App\Enums\VerificationStatus;
use App\Enums\VouchStatus;
use App\Enums\VouchType;
use App\Mail\ApplicationConfirmationMail;
use App\Mail\CandidateShortlistMail;
use App\Mail\ChatReminderMail;
use App\Mail\EvidenceActivityMail;
use App\Mail\InterviewInvitationMail;
use App\Mail\NewApplicationMail;
use App\Mail\NewJobPostedMail;
use App\Mail\NewUserRegisteredMail;
use App\Mail\PaymentAwaitingConfirmationMail;
use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Mail\PayoutNotificationMail;
use App\Mail\PlagiarismAlertMail;
use App\Mail\PlagiarismBanMail;
use App\Mail\PlagiarismBanOverturnedMail;
use App\Mail\PlagiarismWarningMail;
use App\Mail\VerificationApprovedMail;
use App\Mail\VouchReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\Application;
use App\Models\Company;
use App\Models\Evidence;
use App\Models\Job;
use App\Models\Payment;
use App\Models\PlagiarismStrike;
use App\Models\RecruiterInterview;
use App\Models\Skill;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Vouch;
use Illuminate\Contracts\View\View;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Renders every transactional email with realistic sample data so the team
 * can review the layout in the browser before anything is sent.
 *
 * Sample records are created inside a rolled-back transaction, so previewing
 * never persists data and real models give working URLs in the emails.
 */
class EmailPreviewController extends Controller
{
    /**
     * Gallery of every transactional email, rendered with sample data.
     */
    public function index(): View
    {
        return view('emails.preview', [
            'mails' => $this->renderAll(),
        ]);
    }

    /**
     * Render a single email full-page so it can be opened in its own tab.
     */
    public function show(string $mail)
    {
        $mails = $this->renderAll();

        abort_unless(isset($mails[$mail]), 404);

        return response($mails[$mail]['html'])->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * @return array<string, array{label: string, subject: string, html: string}>
     */
    private function renderAll(): array
    {
        $rendered = [];

        DB::beginTransaction();

        try {
            $sample = $this->sampleData();

            foreach ($this->definitions($sample) as $key => $definition) {
                $mailable = $definition['builder']();

                $rendered[$key] = [
                    'label' => $definition['label'],
                    'subject' => $mailable->envelope()->subject,
                    'html' => $mailable->render(),
                ];
            }

            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return $rendered;
    }

    /**
     * Build the mailable for every transactional email.
     *
     * @param  array<string, mixed>  $s
     * @return array<string, array{label: string, builder: callable(): Mailable}>
     */
    private function definitions(array $s): array
    {
        return [
            'welcome' => [
                'label' => 'Welcome',
                'builder' => fn () => new WelcomeMail($s['developer']),
            ],
            'admin-new-user' => [
                'label' => 'New user (admin alert)',
                'builder' => fn () => new NewUserRegisteredMail($s['developer']),
            ],
            'application-confirmation' => [
                'label' => 'Application confirmation',
                'builder' => fn () => new ApplicationConfirmationMail($s['application']),
            ],
            'new-application' => [
                'label' => 'New application (company)',
                'builder' => fn () => new NewApplicationMail($s['application']),
            ],
            'new-application-admin' => [
                'label' => 'New application (admin copy)',
                'builder' => fn () => new NewApplicationMail($s['application'], copy: true),
            ],
            'verification-approved' => [
                'label' => 'Verification approved',
                'builder' => fn () => new VerificationApprovedMail($s['verification']),
            ],
            'verification-approved-admin' => [
                'label' => 'Verification approved (admin copy)',
                'builder' => fn () => new VerificationApprovedMail($s['verification'], copy: true),
            ],
            'vouch-received' => [
                'label' => 'Vouch received',
                'builder' => fn () => new VouchReceivedMail($s['vouch']),
            ],
            'shortlist' => [
                'label' => 'Candidate shortlist',
                'builder' => fn () => new CandidateShortlistMail($s['recruiter'], $s['shortlistRows'], 'Senior Laravel Engineer — Shortlist'),
            ],
            'invoice' => [
                'label' => 'Payment invoice (receipt)',
                'builder' => fn () => new PaymentInvoiceMail($s['paymentFlutterwave']),
            ],
            'payout-worldremit' => [
                'label' => 'Payout notice (WorldRemit)',
                'builder' => fn () => new PayoutNotificationMail($s['paymentWorldRemit']),
            ],
            'payment-awaiting-worldremit' => [
                'label' => 'Payment submitted (admin alert)',
                'builder' => fn () => new PaymentAwaitingConfirmationMail($s['paymentWorldRemitPending']),
            ],
            'payment-received' => [
                'label' => 'Payment received (buyer acknowledgment)',
                'builder' => fn () => new PaymentReceivedMail($s['paymentWorldRemitPending']),
            ],
            'payout-bank' => [
                'label' => 'Payout notice (Bank transfer)',
                'builder' => fn () => new PayoutNotificationMail($s['paymentBank']),
            ],
            'chat-reminder' => [
                'label' => 'Chat reply reminder',
                'builder' => fn () => new ChatReminderMail(
                    $s['developer'],
                    $s['recruiter'],
                    $s['conversation'],
                    $s['chatPreview'],
                ),
            ],
            'interview-invitation' => [
                'label' => 'Interview invitation',
                'builder' => fn () => new InterviewInvitationMail($s['interview']),
            ],
            'new-job-posted' => [
                'label' => 'New job posted',
                'builder' => fn () => new NewJobPostedMail($s['job']),
            ],
            'evidence-analyzed' => [
                'label' => 'Evidence scan ready',
                'builder' => fn () => new EvidenceActivityMail($s['evidence'], analyzed: true),
            ],
            'evidence-added' => [
                'label' => 'Evidence added',
                'builder' => fn () => new EvidenceActivityMail($s['evidence'], analyzed: false),
            ],
            'plagiarism-warning' => [
                'label' => 'Plagiarism warning (offender)',
                'builder' => fn () => new PlagiarismWarningMail($s['plagiarismStrike']),
            ],
            'plagiarism-ban' => [
                'label' => 'Plagiarism ban (offender)',
                'builder' => fn () => new PlagiarismBanMail($s['plagiarismStrike']),
            ],
            'plagiarism-alert' => [
                'label' => 'Plagiarism alert (original owner)',
                'builder' => fn () => new PlagiarismAlertMail($s['plagiarismStrike']),
            ],
            'plagiarism-ban-overturned' => [
                'label' => 'Plagiarism ban overturned (reinstated)',
                'builder' => fn () => new PlagiarismBanOverturnedMail($s['plagiarismStrike']),
            ],
        ];
    }

    /**
     * Create realistic sample records inside the caller's transaction.
     *
     * @return array<string, mixed>
     */
    private function sampleData(): array
    {
        $suffix = strtolower(Str::random(4));

        $developer = User::factory()->create([
            'name' => 'Ava Builds',
            'username' => 'ava-builds-'.$suffix,
            'email' => 'ava-'.$suffix.'@example.com',
            'role' => UserRole::Developer,
            'headline' => 'Senior Laravel & Vue engineer · 8 years building fintech',
            'location' => 'Kampala, Uganda',
            'bio' => 'I turn messy requirements into shippable products. Focused on payments, APIs and developer tools.',
            'github_url' => 'https://github.com/avabuilds',
            'linkedin_url' => 'https://linkedin.com/in/avabuilds',
            'website_url' => 'https://avabuilds.dev',
        ]);

        $recruiter = User::factory()->create([
            'name' => 'Dana Okafor',
            'username' => 'dana-okafor-'.$suffix,
            'email' => 'dana-'.$suffix.'@example.com',
            'role' => UserRole::Recruiter,
            'headline' => 'Talent partner — building evidence-first engineering teams',
            'location' => 'Nairobi, Kenya',
            // Chat access requires verification, so the sample sender is verified.
            'is_verified' => true,
        ]);

        $secondDeveloper = User::factory()->create([
            'name' => 'Noah Mwangi',
            'username' => 'noah-mwangi-'.$suffix,
            'email' => 'noah-'.$suffix.'@example.com',
            'role' => UserRole::Developer,
            'headline' => 'Backend engineer — Go, Postgres and event-driven systems',
            'location' => 'Nairobi, Kenya',
        ]);

        $company = Company::factory()->create([
            'owner_id' => $recruiter->id,
            'name' => 'ProoDev Labs',
            'location' => 'Remote',
        ]);

        $job = Job::factory()->for($company)->create([
            'title' => 'Senior Laravel Engineer',
            'location' => 'Remote',
            'description' => 'Build payment rails and internal tooling for a fast-growing fintech.',
        ]);

        $application = Application::factory()->create([
            'user_id' => $developer->id,
            'job_id' => $job->id,
            'status' => ApplicationStatus::Pending,
            'resume_path' => 'resumes/ava-builds.pdf',
        ]);

        $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.$suffix, 'category' => 'backend']);

        $verification = VerificationRequest::create([
            'user_id' => $developer->id,
            'type' => VerificationRequestType::Employment,
            'label' => 'Senior Laravel Engineer',
            'evidence' => ['github' => 'https://github.com/avabuilds'],
            'status' => VerificationStatus::Approved->value,
            'reviewed_at' => now(),
        ]);

        $vouch = Vouch::create([
            'voucher_id' => $recruiter->id,
            'vouchee_id' => $developer->id,
            'type' => VouchType::Skill,
            'skill_id' => $skill->id,
            'message' => 'Ava shipped our payments rewrite on time and under budget. Deep Laravel knowledge, clean PRs, great communicator.',
            'status' => VouchStatus::Approved,
            'weight' => 3,
        ]);

        $paymentFlutterwave = Payment::factory()->paid()->create([
            'user_id' => $developer->id,
            'purpose' => PaymentPurpose::Credits,
            'amount' => 8,
            'currency' => 'USD',
            'provider' => 'flutterwave',
            'payment_method' => PaymentMethod::Flutterwave,
            'gateway_reference' => 'FLW-7A9C21',
            'status' => PaymentStatus::Paid,
            'created_at' => Carbon::parse('2026-01-15 10:30:00'),
            'paid_at' => now()->subHour(),
        ]);

        $paymentWorldRemit = Payment::factory()->paid()->create([
            'user_id' => $developer->id,
            'purpose' => PaymentPurpose::Credits,
            'amount' => 8,
            'currency' => 'USD',
            'provider' => 'manual',
            'payment_method' => PaymentMethod::WorldRemit,
            'gateway_reference' => 'PDV-KXMRQT',
            'status' => PaymentStatus::Paid,
            'created_at' => Carbon::parse('2026-01-15 10:31:00'),
            'paid_at' => now()->subMinutes(30),
        ]);

        $paymentBank = Payment::factory()->paid()->create([
            'user_id' => $developer->id,
            'purpose' => PaymentPurpose::Verification,
            'amount' => 8,
            'currency' => 'USD',
            'provider' => 'manual',
            'payment_method' => PaymentMethod::Bank,
            'gateway_reference' => 'PDV-TQWNZB',
            'status' => PaymentStatus::Paid,
            'created_at' => Carbon::parse('2026-01-15 10:32:00'),
            'paid_at' => now()->subMinutes(15),
        ]);

        $paymentWorldRemitPending = Payment::factory()->create([
            'user_id' => $developer->id,
            'purpose' => PaymentPurpose::Credits,
            'amount' => 8,
            'currency' => 'USD',
            'provider' => 'manual',
            'payment_method' => PaymentMethod::WorldRemit,
            'gateway_reference' => 'PDV-KXMRQZ',
            'status' => PaymentStatus::Pending,
            'created_at' => Carbon::parse('2026-01-15 10:33:00'),
            'customer_confirmed_at' => now()->subMinutes(10),
        ]);

        $evidence = Evidence::create([
            'user_id' => $developer->id,
            'type' => EvidenceType::GithubRepository,
            'title' => 'proodev/payments-core',
            'url' => 'https://github.com/proodev/payments-core',
            'source' => 'github',
            'status' => EvidenceStatus::Ready,
            'ai_score' => 87,
            'analyzed_at' => now(),
        ]);

        $interview = RecruiterInterview::create([
            'recruiter_id' => $recruiter->id,
            'candidate_id' => $developer->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(3)->setTime(10, 0),
            'mode' => 'video',
        ]);

        $chatPreview = 'Hi Ava — we saw your Senior Laravel Engineer DevID and it matches a role we\'re hiring for at ProoDev Labs. Would you be open to a quick call this week? The role is fully remote and the team ships fast.';

        $plagiarismStrike = PlagiarismStrike::create([
            'offender_id' => $secondDeveloper->id,
            'owner_id' => $developer->id,
            'evidence_id' => $evidence->id,
            'repo_owner' => 'avabuilds',
            'repo_name' => 'payments-core',
            'repo_url' => 'https://github.com/avabuilds/payments-core',
            'strike_number' => 2,
            'action' => 'banned',
            'reason' => 'You claimed avabuilds/payments-core, a repository already claimed on ProoDev by Ava Builds.',
            'notified_at' => now(),
        ]);

        $message = $recruiter->sendMessageTo($developer, $chatPreview);
        $conversation = $message->conversation;

        $shortlistRows = collect([$developer, $secondDeveloper])->map(function (User $user, int $index) {
            return [
                'rank' => $index + 1,
                'name' => $user->name,
                'username' => $user->username,
                'headline' => $user->headline,
                'location' => $user->location,
                'level' => 'Senior',
                'xp' => 1842,
                'reputation' => 876,
                'verified' => $index === 0 ? 'Yes' : 'No',
                'email' => $user->email,
                'skills' => 'Laravel, PHP, Livewire, MySQL, Redis',
                'passport_url' => route('devid', $user->username),
                'avatar' => $user->avatarUrl(),
            ];
        })->all();

        return compact(
            'developer',
            'secondDeveloper',
            'recruiter',
            'company',
            'job',
            'application',
            'verification',
            'vouch',
            'paymentFlutterwave',
            'paymentWorldRemit',
            'paymentBank',
            'paymentWorldRemitPending',
            'evidence',
            'interview',
            'chatPreview',
            'conversation',
            'plagiarismStrike',
            'shortlistRows',
        );
    }
}
