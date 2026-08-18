<?php

namespace App\Actions\Verification;

use App\Enums\VerificationRequestType;
use App\Models\User;
use App\Models\VerificationRequest;

class CreateVerificationRequestAction
{
    public function handle(User $user, VerificationRequestType $type, array $data = []): VerificationRequest
    {
        return VerificationRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'company_name' => $data['company_name'] ?? null,
            'company_domain' => $data['company_domain'] ?? null,
            'label' => $data['label'] ?? null,
            'evidence' => $data['evidence'] ?? null,
            'status' => 'pending',
        ]);
    }
}
