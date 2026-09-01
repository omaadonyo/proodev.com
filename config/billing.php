<?php

return [

    'currency' => env('BILLING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Display exchange rates
    |--------------------------------------------------------------------------
    | Admin dashboards can toggle between the base currency (USD) and UGX.
    | The rate below is a display-only conversion — it never changes how
    | payments are recorded.
    */

    'exchange_rates' => [
        'ugx_per_usd' => (float) env('BILLING_UGX_PER_USD', 3800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Seller / company details shown on invoices & receipts
    |--------------------------------------------------------------------------
    */

    'seller' => [
        'name' => env('BILLING_SELLER_NAME', 'Aletheia Uganda Software Company Limited'),
        'address' => env('BILLING_SELLER_ADDRESS', 'Plot 2141, Luzira Portbell Rd, Natasha Road TankHill Rd'),
        'city' => env('BILLING_SELLER_CITY', 'Kampala'),
        'state' => env('BILLING_SELLER_STATE', ''),
        'postal_code' => env('BILLING_SELLER_POSTAL_CODE', ''),
        'country' => env('BILLING_SELLER_COUNTRY', 'Uganda'),
        'email' => env('BILLING_SELLER_EMAIL', 'sales@proodev.com'),
        'phone' => env('BILLING_SELLER_PHONE', '+256 786 634 306'),
        'tax_id' => env('BILLING_SELLER_TAX_ID', 'UG 1016550521'),
        'website' => env('BILLING_SELLER_WEBSITE', 'https://proodev.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Developer monetization
    |--------------------------------------------------------------------------
    |
    | Verification comes in two plans: a $17 lifetime one-time purchase, or
    | an $8 monthly subscription (renewed manually through checkout since
    | payments are admin-confirmed). Both grant the badge and a proo.dev/<name>
    | short link. Credits power AI link analyses beyond the free daily
    | allowance. One credit = tokens_per_credit tokens and covers roughly one
    | analysis (1 credit = 1000 tokens, 1 credit per link).
    |
    */

    'developer' => [
        'verification' => [
            'lifetime_price' => 17,
            'monthly_price' => 8,
            'interval_days' => 30,
            'short_domain' => env('SHORT_DOMAIN', 'proo.dev'),
        ],
        'credits' => [
            'tokens_per_credit' => 1000,
            'analysis_cost' => 1,
            'bundles' => [
                ['credits' => 8, 'price' => 8],
                ['credits' => 32, 'price' => 24],
                ['credits' => 96, 'price' => 60],
            ],
        ],
        'auto_scan' => [
            'label' => 'Repo Auto-Scan',
            'price' => 8,
            'interval_days' => 30,
        ],
        'daily_free_submissions' => 3,
        'submission_credit_cost' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Company / recruiter plans
    |--------------------------------------------------------------------------
    |
    | Recruiters and companies share one role and one feature set. The standard
    | plan is $299/month. The Recruiter Intelligence Suite is a higher-priced
    | tier with the SAME features: $599 first month then $199/month.
    |
    */

    'companies' => [
        'trial' => [
            'label' => 'Free',
            'price' => 0,
            'job_limit' => 1,
            'features' => [
                '1 active job post',
                'Company profile page',
                'Browse the talent pool',
                'Receive applications',
            ],
        ],
        'recruiter' => [
            'label' => 'Recruiter',
            'price' => 299,
            'job_limit' => 1,
            'features' => [
                '1 active job post',
                'Full applicant tracking',
                'Recruiter notes & shortlisting',
                'Candidate intelligence reports',
                'Side-by-side candidate comparison',
                'Evidence-based talent search',
                'Team fit & hiring risk analysis',
                'Resume vs evidence validation',
                'AI interview question generator',
                'Executive-level candidate exports',
                'Verified badge on your profile',
                'Priority listing in talent search',
            ],
        ],
        'intelligence' => [
            'label' => 'Recruiter Intelligence Suite',
            'price' => 199,
            'first_month_price' => 599,
            'job_limit' => null,
            'features' => [
                'Unlimited job posts',
                'Full applicant tracking',
                'Recruiter notes & shortlisting',
                'Candidate intelligence reports',
                'Side-by-side candidate comparison',
                'Evidence-based talent search',
                'Team fit & hiring risk analysis',
                'Resume vs evidence validation',
                'AI interview question generator',
                'Executive-level candidate exports',
                'Verified badge on your profile',
                'Priority listing in talent search',
            ],
        ],
        'verification' => [
            'label' => 'Company Verification',
            'price' => 299,
            'features' => [
                'Verified badge on your company profile',
                'Priority listing in talent search',
                'Trust signal for applicants',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Job post credits
        |--------------------------------------------------------------------------
        |
        | Companies buy job post credits in bundles. Each credit allows one
        | active (open) job posting. $299 buys a single post; $599 buys three.
        | Admins can grant additional credits from the admin panel.
        |
        */

        'job_posts' => [
            'bundles' => [
                ['posts' => 1, 'price' => 299],
            ],
        ],
    ],
];
