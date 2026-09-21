<?php

namespace App\Notifications\Blog;

use App\Enums\NotificationCategory;
use App\Models\BlogComment;
use App\Notifications\AdminNotification;
use Illuminate\Support\Str;

/**
 * In-app only: a public comment is waiting in the moderation queue.
 * Comments are the one public form without an attachment/consent step,
 * so they are kept off email to avoid a spam wave hitting inboxes.
 */
class CommentAwaitingModerationAlert extends AdminNotification
{
    public function __construct(public BlogComment $comment)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Comment;
    }

    /**
     * @return list<string>
     */
    public function supportedChannels(): array
    {
        return ['database'];
    }

    public function title(): string
    {
        return "New comment awaiting moderation on “{$this->comment->post->title}”";
    }

    public function message(): string
    {
        return $this->comment->name.': '.Str::limit($this->comment->body, 120);
    }

    public function actionUrl(): ?string
    {
        return route('admin.blog-comments.index', ['status' => 'pending']);
    }

    public function actionLabel(): string
    {
        return 'Moderate Comments';
    }
}
