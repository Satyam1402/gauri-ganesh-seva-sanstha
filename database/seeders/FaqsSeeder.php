<?php

namespace Database\Seeders;

use App\Enums\FaqStatus;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqsSeeder extends Seeder
{
    /**
     * Starter FAQs so every public view has content. Answers are Markdown.
     * Content is generic and safe to keep, but the organisation should
     * review each answer (especially tax and payment details) before launch.
     */
    public function run(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $categories = FaqCategory::query()->pluck('id', 'slug');

        $faqs = [
            ['general', 'What does Gauri Ganesh Seva Sanstha do?', "We are a registered charitable trust based in Pune working in four areas:\n\n- **Food** — regular community meal drives and ration support\n- **Education** — study kits, evening tuition and school fee support\n- **Health** — free medical check-up camps and medicine distribution\n- **Welfare** — clothing drives and support for families in crisis\n\nEvery programme is run by volunteers and funded entirely by donations.", true],
            ['general', 'How can I request help for my family?', 'Please reach us through the [Contact page](/contact) and choose *Request Help* as the category, or call the number listed there. All requests are handled confidentially and reviewed within a few working days.', true],
            ['donations', 'Is my donation tax-deductible?', "Yes. The Sanstha is registered under **Section 80G** of the Income Tax Act, so donations from Indian taxpayers are eligible for a deduction.\n\nYou will receive an 80G receipt by email after your payment is confirmed. Please make sure the name and PAN you enter match your tax records.", true],
            ['donations', 'How do I know my donation is used well?', "We publish fund-usage summaries and impact updates for every campaign, and donors receive a follow-up with photos from the drive their contribution supported.\n\nOur audited accounts and registration documents are available on the [About page](/about).", true],
            ['donations', 'Can I donate to a specific cause or campaign?', 'Yes — each active campaign has its own donation page. Choose a campaign from the [Campaigns page](/campaigns) and your gift will be earmarked for it. General donations go where the need is greatest.', false],
            ['donations', 'Can I set up a monthly donation?', 'Monthly giving is available through UPI autopay and card standing instructions. Select *Monthly* on the donation form; you can pause or cancel any time by contacting us.', false],
            ['payment', 'Which payment methods do you accept?', "You can donate using:\n\n- UPI (Google Pay, PhonePe, Paytm, BHIM)\n- Debit and credit cards\n- Net banking\n- Direct bank transfer (NEFT/IMPS)\n\nOnline payments are processed by Razorpay; we never see or store your card details.", false],
            ['payment', 'I paid but did not receive a receipt. What should I do?', 'Receipts are emailed within a few minutes of a successful payment. Check your spam folder first; if it is still missing after 24 hours, [contact us](/contact) with your payment reference and we will resend it.', false],
            ['volunteers', 'Can I volunteer without a long-term commitment?', 'Absolutely. Most of our volunteers join individual drives — a Sunday meal distribution or a one-day medical camp. There is no minimum commitment; regular roles are available if you want them.', true],
            ['volunteers', 'Is there a minimum age to volunteer?', 'Volunteers must be **16 or older**. Volunteers under 18 need a signed consent form from a parent or guardian, which we send after you apply.', false],
            ['volunteers', 'Do I need any special skills?', 'No. Most roles need only time and willingness. If you have professional skills — medical, teaching, design, accounting — tell us on the [application form](/volunteer) and we will match you to where they help most.', false],
            ['activities', 'Where do your activities take place?', 'Most drives happen in and around Pune — Hadapsar, Yerawada, and nearby villages. Each activity page lists the location and date, and upcoming ones appear on the [Events page](/events).', false],
            ['events', 'Do I need to register to attend an event?', 'Some events (medical camps, blood donation drives) need registration so we can plan supplies. If an event requires it, a registration form appears on its page; otherwise just turn up.', false],
            ['food-distribution', 'How often do you run food distribution drives?', 'Community meal drives run every Sunday, with additional distributions during festivals and emergencies. Ration kits for families are distributed monthly.', false],
            ['education', 'Who is eligible for education support?', 'Children from families with a household income below the state poverty line, and children of daily-wage workers, are prioritised. Apply via the [Contact page](/contact) with the child\'s school details.', false],
            ['medical-assistance', 'Are the medical camps free?', 'Yes. Check-ups, screenings and basic medicines at our camps are completely free. Where a specialist referral is needed, we help arrange it at partner hospitals at concessional rates.', false],
            ['organization', 'Is the Sanstha a registered organisation?', 'Yes. We are a registered public charitable trust with **12A** and **80G** registrations. Our registration numbers and certificates are published on the [About page](/about).', false],
            ['organization', 'How is the Sanstha governed?', 'A board of trustees oversees the organisation, and accounts are audited annually by an independent chartered accountant. Audited statements are available on request.', false],
        ];

        foreach ($faqs as $order => [$slug, $question, $answer, $featured]) {
            Faq::create([
                'faq_category_id' => $categories[$slug] ?? null,
                'question' => $question,
                'answer' => $answer,
                'status' => FaqStatus::Published->value,
                'is_featured' => $featured,
                'display_order' => $order,
                'published_at' => now()->subDays(count($faqs) - $order),
            ]);
        }

        // One draft so the admin listing shows the workflow states.
        Faq::create([
            'faq_category_id' => $categories['general'] ?? null,
            'question' => 'Can I visit your office?',
            'answer' => 'Visits are welcome by appointment on weekdays between 10 am and 5 pm. Please write to us first so someone is available to meet you.',
            'status' => FaqStatus::Draft->value,
            'display_order' => count($faqs),
            'admin_notes' => 'Confirm office hours with the trustees before publishing.',
        ]);
    }
}
