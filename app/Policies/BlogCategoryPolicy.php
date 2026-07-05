<?php

namespace App\Policies;

use App\Models\BlogCategory;
use App\Models\User;

class BlogCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage blog');
    }

    public function view(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('manage blog');
    }

    public function create(User $user): bool
    {
        return $user->can('manage blog');
    }

    public function update(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('manage blog');
    }

    public function delete(User $user, BlogCategory $blogCategory): bool
    {
        return $user->can('manage blog');
    }
}
