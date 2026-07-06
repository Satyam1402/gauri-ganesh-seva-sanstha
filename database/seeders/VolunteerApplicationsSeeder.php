<?php

namespace Database\Seeders;

use App\Enums\CommunicationMethod;
use App\Enums\Gender;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Models\VolunteerApplication;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VolunteerApplicationsSeeder extends Seeder
{
    /**
     * Seed a few sample applications across the review pipeline so the admin
     * screen is demonstrable out of the box. Skipped when data already exists.
     */
    public function run(): void
    {
        if (VolunteerApplication::query()->exists()) {
            return;
        }

        $applications = [
            [
                'first_name' => 'Sneha',
                'last_name' => 'Kulkarni',
                'gender' => Gender::Female->value,
                'date_of_birth' => '1998-04-12',
                'email' => 'sneha.kulkarni@example.com',
                'phone' => '9812345670',
                'city' => 'Pune',
                'occupation' => 'Software Engineer',
                'organization' => 'TechServe Solutions',
                'skills' => 'Teaching basic computers, content writing, event photography',
                'experience' => 'Taught weekend computer classes at a community centre for two years.',
                'areas_of_interest' => ['education_teaching', 'media_content'],
                'availability' => VolunteerAvailability::Weekends->value,
                'status' => VolunteerApplicationStatus::Pending->value,
            ],
            [
                'first_name' => 'Rohan',
                'last_name' => 'Deshmukh',
                'gender' => Gender::Male->value,
                'date_of_birth' => '1992-11-03',
                'email' => 'rohan.deshmukh@example.com',
                'phone' => '9823456781',
                'city' => 'Pune',
                'occupation' => 'General Physician',
                'organization' => 'City Care Clinic',
                'skills' => 'General medicine, first aid training, health screening',
                'experience' => 'Volunteered at rural health camps during residency.',
                'areas_of_interest' => ['medical_health', 'field_work'],
                'availability' => VolunteerAvailability::Flexible->value,
                'status' => VolunteerApplicationStatus::UnderReview->value,
            ],
            [
                'first_name' => 'Meera',
                'last_name' => 'Joshi',
                'gender' => Gender::Female->value,
                'date_of_birth' => '2003-06-25',
                'email' => 'meera.joshi@example.com',
                'phone' => '9834567892',
                'city' => 'Mumbai',
                'occupation' => 'Student',
                'organization' => 'Mumbai University',
                'skills' => 'Social media management, poster design, fundraising drives',
                'experience' => null,
                'areas_of_interest' => ['fundraising', 'media_content', 'event_management'],
                'availability' => VolunteerAvailability::Both->value,
                'status' => VolunteerApplicationStatus::Approved->value,
            ],
            [
                'first_name' => 'Anil',
                'last_name' => 'Pawar',
                'gender' => Gender::Male->value,
                'date_of_birth' => '1985-01-17',
                'email' => 'anil.pawar@example.com',
                'phone' => '9845678903',
                'city' => 'Pune',
                'occupation' => 'Logistics Manager',
                'organization' => null,
                'skills' => 'Driving, warehouse coordination, food handling',
                'experience' => 'Coordinated grocery-kit distribution during the 2020 lockdown.',
                'areas_of_interest' => ['food_distribution', 'field_work'],
                'availability' => VolunteerAvailability::Weekdays->value,
                'status' => VolunteerApplicationStatus::OnHold->value,
            ],
        ];

        foreach ($applications as $data) {
            VolunteerApplication::create([
                ...$data,
                // DatabaseSeeder runs WithoutModelEvents, so the creating()
                // hook never fires — set the reference explicitly.
                'reference' => (string) Str::uuid(),
                'alternate_phone' => null,
                'address' => '123 Seva Marg, Shivaji Nagar',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pin_code' => '411005',
                'emergency_contact_name' => 'Family Contact',
                'emergency_contact_phone' => '9900112233',
                'medical_information' => null,
                'message' => 'I would love to contribute to the Sanstha\'s work.',
                'preferred_communication_method' => CommunicationMethod::WhatsApp->value,
                'consented_at' => now(),
            ]);
        }
    }
}
