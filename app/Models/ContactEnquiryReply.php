<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEnquiryReply extends Model
{
    protected $fillable = [
        'contact_enquiry_id',
        'user_id',
        'message',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(ContactEnquiry::class, 'contact_enquiry_id');
    }

    /**
     * Staff member who sent the reply.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
