<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;

class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage testimonials');
    }

    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage testimonials');
    }

    public function create(User $user): bool
    {
        return $user->can('manage testimonials');
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage testimonials');
    }

    public function delete(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage testimonials');
    }

    public function restore(User $user, Testimonial $testimonial): bool
    {
        return $user->can('manage testimonials');
    }
}
