<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlugRedirect extends Model
{
    protected $fillable = [
        'model_type',
        'old_slug',
        'new_slug',
    ];
}
