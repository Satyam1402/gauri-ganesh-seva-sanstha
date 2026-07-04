<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeSectionButton extends Model
{
    protected $fillable = [
        'home_section_id',
        'label',
        'url',
        'variant',
        'order_column',
    ];

    public function homeSection(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class);
    }
}
