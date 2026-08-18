<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Services\UserAgentParser;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Records a login event (user id, ip address and user agent) into the
 * audit_logs table so admins can review the analytics users log.
 */
class RecordLogin
{
    public function __construct(protected Request $request) {}

    public function handle(Login $event): void
    {
        $userAgent = $this->request->userAgent();

        AuditLog::create([
            'user_id' => $event->user?->getAuthIdentifier(),
            'action' => 'login',
            'data' => [
                'user_agent' => $userAgent,
                'device' => UserAgentParser::parse($userAgent),
            ],
            'ip_address' => $this->request->ip(),
        ]);
    }
}
