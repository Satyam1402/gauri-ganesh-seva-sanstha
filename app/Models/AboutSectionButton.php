<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutSectionButton extends Model
{
    protected $fillable = [
        'about_section_id',
        'label',
        'url',
        'variant',
        'order_column',
    ];

    public function aboutSection(): BelongsTo
    {
        return $this->belongsTo(AboutSection::class);
    }
}
