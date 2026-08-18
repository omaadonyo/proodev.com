<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\User;
use App\Services\MentionService;
use Illuminate\Database\Eloquent\Model;

class AddCommentAction
{
    public function __construct(private MentionService $mentions) {}

    public function handle(User $user, Model $commentable, string $body, ?int $parentId = null): Comment
    {
        $comment = Comment::create([
            'commentable_type' => $commentable::class,
            'commentable_id' => $commentable->getKey(),
            'user_id' => $user->id,
            'parent_id' => $parentId,
            'body' => $body,
        ]);

        $this->mentions->notify($comment);

        return $comment;
    }
}
