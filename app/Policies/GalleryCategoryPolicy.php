<?php

namespace App\Policies;

use App\Models\GalleryCategory;
use App\Models\User;

class GalleryCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage gallery');
    }

    public function view(User $user, GalleryCategory $galleryCategory): bool
    {
        return $user->can('manage gallery');
    }

    public function create(User $user): bool
    {
        return $user->can('manage gallery');
    }

    public function update(User $user, GalleryCategory $galleryCategory): bool
    {
        return $user->can('manage gallery');
    }

    public function delete(User $user, GalleryCategory $galleryCategory): bool
    {
        return $user->can('manage gallery');
    }
}
