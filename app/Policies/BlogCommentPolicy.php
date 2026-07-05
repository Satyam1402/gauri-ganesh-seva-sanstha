<?php

namespace App\Policies;

use App\Models\BlogComment;
use App\Models\User;

class BlogCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage blog');
    }

    public function view(User $user, BlogComment $blogComment): bool
    {
        return $user->can('manage blog');
    }

    public function update(User $user, BlogComment $blogComment): bool
    {
        return $user->can('manage blog');
    }

    public function delete(User $user, BlogComment $blogComment): bool
    {
        return $user->can('manage blog');
    }
}
