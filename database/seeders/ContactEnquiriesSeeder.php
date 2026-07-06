<?php

namespace Database\Seeders;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Models\ContactEnquiry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContactEnquiriesSeeder extends Seeder
{
    /**
     * Seed a few sample enquiries across the triage pipeline so the admin
     * screen is demonstrable out of the box. Skipped when data already exists.
     */
    public function run(): void
    {
        if (ContactEnquiry::query()->exists()) {
            return;
        }

        $enquiries = [
            [
                'name' => 'Prakash Jadhav',
                'email' => 'prakash.jadhav@example.com',
                'phone' => '9812345671',
                'subject' => 'Question about 80G receipt for my donation',
                'category' => EnquiryCategory::Donation->value,
                'message' => 'I donated ₹5,000 last week through your website but have not received my 80G tax receipt yet. Could you please check and resend it?',
                'status' => EnquiryStatus::New->value,
            ],
            [
                'name' => 'Anita Deshpande',
                'email' => 'anita.deshpande@example.com',
                'phone' => '9823456782',
                'subject' => 'CSR partnership proposal from our company',
                'category' => EnquiryCategory::Partnership->value,
                'message' => 'We are a Pune-based IT company looking to partner with a local NGO for our CSR programme. We are particularly interested in your education initiatives. Could we schedule a call?',
                'status' => EnquiryStatus::InProgress->value,
            ],
            [
                'name' => 'Vikram Sathe',
                'email' => 'vikram.sathe@example.com',
                'phone' => '9834567893',
                'subject' => 'Coverage request for your next medical camp',
                'category' => EnquiryCategory::Media->value,
                'message' => 'I write for a local weekly and would love to cover your next medical camp. Please share the schedule and a media contact.',
                'status' => EnquiryStatus::Resolved->value,
            ],
            [
                'name' => 'Sunita Bhosale',
                'email' => 'sunita.bhosale@example.com',
                'phone' => '9845678904',
                'subject' => 'Suggestion: weekend food drive in Hadapsar',
                'category' => EnquiryCategory::Suggestion->value,
                'message' => 'There are several families near the Hadapsar railway crossing who could really use your weekend food drive. Please consider extending the route.',
                'status' => EnquiryStatus::Closed->value,
            ],
        ];

        foreach ($enquiries as $data) {
            ContactEnquiry::create([
                ...$data,
                // DatabaseSeeder runs WithoutModelEvents, so the creating()
                // hook never fires — set the reference explicitly.
                'reference' => (string) Str::uuid(),
                'consented_at' => now(),
                'ip_address' => '127.0.0.1',
            ]);
        }
    }
}
