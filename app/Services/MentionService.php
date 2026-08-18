<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Collection;

class MentionService
{
    /**
     * Parse @username mentions inside a body and notify those users.
     *
     * @param  array<int, User>  $alreadyNotified
     */
    public function notify(Comment $comment, array $alreadyNotified = []): void
    {
        foreach ($this->extract($comment->body) as $username) {
            if (in_array($username, $alreadyNotified, true)) {
                continue;
            }

            $user = User::where('username', $username)->first();

            if (! $user || $user->id === $comment->user_id) {
                continue;
            }

            $user->notify(new MentionNotification($comment));
        }
    }

    /**
     * @return Collection<int, string>
     */
    public function extract(string $body): Collection
    {
        preg_match_all('/@([a-zA-Z0-9_-]{3,32})/u', $body, $matches);

        return collect($matches[1] ?? [])->unique();
    }
}
