<?php

namespace App\Policies;

use App\Models\GalleryAlbum;
use App\Models\User;

class GalleryAlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage gallery');
    }

    public function view(User $user, GalleryAlbum $galleryAlbum): bool
    {
        return $user->can('manage gallery');
    }

    public function create(User $user): bool
    {
        return $user->can('manage gallery');
    }

    public function update(User $user, GalleryAlbum $galleryAlbum): bool
    {
        return $user->can('manage gallery');
    }

    public function delete(User $user, GalleryAlbum $galleryAlbum): bool
    {
        return $user->can('manage gallery');
    }

    public function restore(User $user, GalleryAlbum $galleryAlbum): bool
    {
        return $user->can('manage gallery');
    }
}
