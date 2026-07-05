<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Interfaces\BlogCommentRepositoryInterface;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Validation\ValidationException;

class BlogCommentService
{
    public function __construct(private BlogCommentRepositoryInterface $comments) {}

    /**
     * Public comment submission — always lands as Pending so an admin
     * approves it before it appears on the site.
     *
     * @param  array<string, mixed>  $data
     */
    public function submit(BlogPost $post, array $data, ?string $ipAddress = null): BlogComment
    {
        if (! $post->allow_comments || $post->status !== PostStatus::Published) {
            throw ValidationException::withMessages([
                'comment' => 'Comments are closed for this article.',
            ]);
        }

        return $post->comments()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'body' => $data['body'],
            'status' => CommentStatus::Pending->value,
            'ip_address' => $ipAddress,
        ]);
    }

    public function updateStatus(BlogComment $comment, string $status): BlogComment
    {
        return $this->comments->update($comment, ['status' => $status]);
    }

    public function delete(BlogComment $comment): bool
    {
        return $this->comments->delete($comment);
    }
}
