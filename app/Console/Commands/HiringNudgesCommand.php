<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\HiringTransparencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class HiringNudgesCommand extends Command
{
    protected $signature = 'os:hiring-nudges';

    protected $description = 'Email companies whose applications have been waiting too long for an update';

    public function handle(HiringTransparencyService $transparency): int
    {
        $staleTotal = 0;
        $companiesNudged = 0;

        Company::query()->where('status', \App\Enums\CompanyStatus::Approved)->chunkById(100, function ($companies) use ($transparency, &$staleTotal, &$companiesNudged) {
            foreach ($companies as $company) {
                $stale = $transparency->staleForCompany($company);

                if ($stale->isEmpty()) {
                    continue;
                }

                $staleTotal += $stale->count();
                $companiesNudged++;

                $owner = $company->owner;

                if ($owner && $owner->email) {
                    Mail::raw(
                        "{$stale->count()} candidate(s) have been waiting for an update on your ProoDev openings. "
                        .'Review them, shortlist, reject or pause the role to keep your pipeline healthy.',
                        function ($message) use ($owner) {
                            $message->to($owner->email)->subject('Candidate updates needed — '.$stale->count().' waiting');
                        },
                    );
                }
            }
        });

        $this->info("Nudged {$companiesNudged} company(ies) about {$staleTotal} stale application(s).");

        return self::SUCCESS;
    }
}