<?php

namespace App\Services;

use App\Models\User;

/**
 * Sends the default admin welcome messages to a user.
 *
 * Two messages land in a private conversation with the platform admin, so
 * users see an unread badge of 2 until they read them. Only verified users
 * can access chats — unverified recipients see the badge but must verify
 * before they can read.
 */
class WelcomeMessageService
{
    /**
     * Ensure both welcome messages are present for the user.
     *
     * @return bool True when any new message was sent.
     */
    public function sendTo(User $user): bool
    {
        try {
            $admin = User::query()
                ->where('email', config('platform.admin_email'))
                ->where('id', '!=', $user->id)
                ->first();

            if (! $admin) {
                return false;
            }

            $conversation = $admin->createConversationWith($user);

            if (! $conversation) {
                return false;
            }

            $participant = $conversation->participants()
                ->where('participantable_type', $admin->getMorphClass())
                ->where('participantable_id', $admin->id)
                ->first();

            if (! $participant) {
                return false;
            }

            $existing = $conversation->messages()
                ->where('participant_id', $participant->id)
                ->count();

            $sent = false;

            if ($existing === 0) {
                $firstName = str($user->name)->before(' ');

                $conversation->messages()->create([
                    'participant_id' => $participant->id,
                    'body' => "Hi {$firstName}! Welcome to ProoDev — your account is now verified. "
                        .'You can chat with other verified engineers and get discovered by companies through your work.',
                ]);

                $sent = true;
                $existing++;
            }

            if ($existing === 1) {
                $conversation->messages()->create([
                    'participant_id' => $participant->id,
                    'body' => 'Feel free to reply here if you ever need help — the ProoDev team responds personally.',
                ]);

                $sent = true;
            }

            if ($sent) {
                $conversation->touch();
            }

            return $sent;
        } catch (\Throwable) {
            return false;
        }
    }
}
