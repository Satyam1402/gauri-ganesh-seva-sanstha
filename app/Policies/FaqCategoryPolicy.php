<?php

namespace App\Policies;

use App\Models\FaqCategory;
use App\Models\User;

class FaqCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage faqs');
    }

    public function view(User $user, FaqCategory $faqCategory): bool
    {
        return $user->can('manage faqs');
    }

    public function create(User $user): bool
    {
        return $user->can('manage faqs');
    }

    public function update(User $user, FaqCategory $faqCategory): bool
    {
        return $user->can('manage faqs');
    }

    public function delete(User $user, FaqCategory $faqCategory): bool
    {
        return $user->can('manage faqs');
    }
}
