<?php

return [
    // Legacy / transitional feature toggles. Values are read here and wired
    // into Laravel Pennant at boot time (see AppServiceProvider::defineFeatures).
    'battles' => env('FEATURE_BATTLES', false),
    'linkedin_onboarding' => env('FEATURE_LINKEDIN_ONBOARDING', false),
    'profile_completion' => env('FEATURE_PROFILE_COMPLETION', true),
    'evidence_pipeline' => env('FEATURE_EVIDENCE_PIPELINE', true),
    'companies' => env('FEATURE_COMPANIES', true),
    'credits' => env('FEATURE_CREDITS', true),
    'verification' => env('FEATURE_VERIFICATION', true),
    'auto_scan' => env('FEATURE_AUTO_SCAN', true),
    'public_presence' => env('FEATURE_PUBLIC_PRESENCE', true),
];
