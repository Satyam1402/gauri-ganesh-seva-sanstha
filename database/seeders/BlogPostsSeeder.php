<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostsSeeder extends Seeder
{
    /**
     * Demo blog posts covering the main content types (news, success story,
     * awareness article, announcement, press release) plus one draft and one
     * scheduled post so every admin state is visible out of the box.
     */
    public function run(): void
    {
        $author = User::orderBy('id')->first();

        if ($author === null) {
            $this->command?->warn('BlogPostsSeeder skipped — no users exist yet.');

            return;
        }

        foreach ($this->posts() as $post) {
            $category = BlogCategory::where('slug', $post['category'])->first();

            $model = BlogPost::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    'blog_category_id' => $category?->id,
                    'user_id' => $author->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'published_at' => $post['published_at'],
                    'reading_minutes' => max(1, (int) ceil(str_word_count(strip_tags($post['content'])) / 200)),
                    'views_count' => $post['views'],
                    'allow_comments' => true,
                    'status' => $post['status'],
                    'is_featured' => $post['featured'],
                ]
            );

            $tagIds = collect($post['tags'])->map(function (string $name) {
                return BlogTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id;
            });

            $model->tags()->sync($tagIds->all());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function posts(): array
    {
        return [
            [
                'title' => 'Winter Blanket Drive Reaches 1,200 Families This Season',
                'category' => 'news',
                'excerpt' => 'Our largest winter drive yet — volunteers distributed blankets and warm clothing across 14 localities, reaching over 1,200 families before the coldest weeks arrived.',
                'content' => "Winters are hardest on the families who have the least. This year our teams set out early — planning began in October, and by mid-December the first trucks were rolling out.\n\n## What we achieved\n\n- **1,200+ families** received blankets and warm clothing\n- **14 localities** covered across the district\n- **85 volunteers** gave their weekends to sorting, packing, and distribution\n\n## How it worked\n\nEach locality was surveyed in advance by our volunteer coordinators so that distribution lists were ready before the trucks arrived. That meant no queues in the cold and no family left out.\n\n> \"We didn't have to ask. They came to our door with the blankets.\" — a resident of one of the settlements we visited\n\n## What's next\n\nThe drive continues through January. If you'd like to contribute, a single donation of ₹300 covers one family's winter kit.",
                'tags' => ['winter drive', 'blankets', 'relief'],
                'published_at' => now()->subDays(3)->setTime(9, 0),
                'status' => 'published',
                'featured' => true,
                'views' => 412,
            ],
            [
                'title' => 'From Daily Wages to a Degree: Meena\'s Journey',
                'category' => 'success-stories',
                'excerpt' => 'Three years ago Meena was helping her mother at a construction site. This month she enrolled in a B.Com programme — here is how a community scholarship changed her path.',
                'content' => "When our education team first met Meena, she was 16 and had been out of school for two years. Her family needed the income from her daily-wage work, and school fees were out of reach.\n\n## The turning point\n\nThrough our education sponsorship programme, Meena's school fees, books, and travel were covered — and just as importantly, our counsellors worked with her family so that returning to school didn't feel like a sacrifice.\n\n## Three years later\n\n- Passed her Class 12 board exams with **78%**\n- Earned a merit seat in a local commerce college\n- Now tutors two younger children in her neighbourhood\n\n> \"I thought my studies were finished. Now I tell the younger girls — it is never finished unless you stop.\"\n\n## Sponsor a student\n\n₹1,500 a month keeps one child in school with books, fees, and transport covered. Every Meena starts with one sponsor.",
                'tags' => ['education', 'scholarship', 'success story'],
                'published_at' => now()->subDays(7)->setTime(10, 30),
                'status' => 'published',
                'featured' => true,
                'views' => 655,
            ],
            [
                'title' => 'Free Health Checkup Camp Serves 340 Patients in a Single Day',
                'category' => 'medical-camps',
                'excerpt' => 'Doctors, nurses, and volunteers came together for a full-day health camp offering general checkups, eye screening, and free medicines to 340 patients.',
                'content' => "Our latest medical camp brought free healthcare directly to a community where the nearest clinic is over an hour away.\n\n## Services offered\n\n- General physician consultations\n- Blood pressure and diabetes screening\n- Eye checkups with free spectacles for 60 patients\n- Free medicines dispensed on the spot\n\n## The numbers\n\n| Service | Patients |\n| --- | --- |\n| General checkup | 210 |\n| Eye screening | 95 |\n| Referrals to hospital | 35 |\n\n## Thank you\n\nThis camp ran on the generosity of 6 volunteer doctors and 22 volunteers. Our next camp is already being planned — follow the events page to register as a volunteer.",
                'tags' => ['health', 'medical camp', 'volunteers'],
                'published_at' => now()->subDays(12)->setTime(14, 0),
                'status' => 'published',
                'featured' => true,
                'views' => 289,
            ],
            [
                'title' => 'Why Community Kitchens Work: Lessons from 50,000 Meals',
                'category' => 'awareness',
                'excerpt' => 'After serving fifty thousand meals through community kitchens, we look at what makes this model so effective — and how you can help it grow.',
                'content' => "Hunger is rarely about a shortage of food — it is about access. Community kitchens close that gap, and after 50,000 meals served, the model has proven itself.\n\n## What makes it work\n\n1. **Local sourcing** — vegetables and grains come from nearby markets, keeping costs low and money in the community.\n2. **Volunteer rotation** — no volunteer cooks more than one shift a week, so no one burns out.\n3. **Dignity first** — meals are served at tables, not handed out in queues.\n\n## The cost of one meal\n\nA full, nutritious meal costs us just **₹18** to prepare and serve. That means a ₹500 donation feeds 27 people.\n\n## How to help\n\nDonate, volunteer for a cooking shift, or simply spread the word. Community kitchens grow one neighbourhood at a time.",
                'tags' => ['food distribution', 'community kitchen', 'awareness'],
                'published_at' => now()->subDays(18)->setTime(11, 0),
                'status' => 'published',
                'featured' => false,
                'views' => 178,
            ],
            [
                'title' => 'Annual Report 2025 Released: A Year of Growing Impact',
                'category' => 'press-releases',
                'excerpt' => 'Our Annual Report for 2025 is now available, documenting a year in which programmes expanded to 9 new localities and volunteer strength doubled.',
                'content' => "We are pleased to announce the release of our **Annual Report 2025**.\n\n## Highlights from the year\n\n- Programmes expanded to **9 new localities**\n- Volunteer base grew from 210 to **430 active volunteers**\n- Over **1.2 lakh meals** served through food programmes\n- **28 medical camps** conducted with partner hospitals\n\n## Transparency commitment\n\nThe full report includes audited financial statements, programme-wise expenditure, and donor acknowledgements. As always, every rupee is accounted for publicly.\n\nCopies are available at our office and digitally on request. For press enquiries, please contact our office through the contact page.",
                'tags' => ['annual report', 'press release', 'transparency'],
                'published_at' => now()->subDays(25)->setTime(9, 30),
                'status' => 'published',
                'featured' => false,
                'views' => 96,
            ],
            [
                'title' => 'Volunteer Orientation Day: What to Expect at Your First Session',
                'category' => 'general',
                'excerpt' => 'Joining us as a volunteer? Here is a walkthrough of orientation day — from paperwork to your first field visit — so you arrive prepared.',
                'content' => "Every month we welcome a new batch of volunteers, and the questions are always the same: *What should I bring? How long does it take? When do I actually get to help?*\n\n## The schedule\n\n- **10:00 AM** — Welcome and introduction to our programmes\n- **11:00 AM** — Safety and conduct guidelines\n- **12:00 PM** — Team assignments based on your interests\n- **1:00 PM** — Lunch with your team (from our community kitchen!)\n- **2:00 PM** — A short field visit with an experienced volunteer\n\n## What to bring\n\nJust a government ID and comfortable shoes. Everything else — including your volunteer kit — is provided.\n\n## Ready to join?\n\nWatch the events page for the next orientation date. We can't wait to meet you.",
                'tags' => ['volunteers', 'orientation'],
                'published_at' => now()->subDays(35)->setTime(16, 0),
                'status' => 'published',
                'featured' => false,
                'views' => 143,
            ],
            [
                'title' => 'Announcing Our New Skill Development Centre',
                'category' => 'announcements',
                'excerpt' => 'A dedicated centre for tailoring, computer literacy, and spoken English classes opens next month — registrations begin soon.',
                'content' => "We are delighted to announce that our new **Skill Development Centre** opens next month.\n\n## Courses on offer\n\n- Tailoring and garment making (3-month certificate)\n- Basic computer literacy (6-week course)\n- Spoken English for job seekers (8-week course)\n\n## Who can apply\n\nAll courses are **free of charge** and open to anyone from the communities we serve, with priority for women re-entering the workforce.\n\nRegistration details will be published on this blog and on our notice boards. Stay tuned!",
                'tags' => ['announcement', 'skill development', 'education'],
                'published_at' => now()->addDays(7)->setTime(9, 0),
                'status' => 'published',
                'featured' => false,
                'views' => 0,
            ],
            [
                'title' => 'Monsoon Relief Preparedness: Our 2026 Action Plan',
                'category' => 'news',
                'excerpt' => 'A look at how we are preparing supply chains, shelters, and volunteer teams ahead of this year\'s monsoon season.',
                'content' => "Every monsoon brings flooding to low-lying settlements, and every year preparation matters more than reaction.\n\n## The 2026 plan\n\n- Pre-positioned relief kits in 4 storage points\n- A trained rapid-response volunteer team of 40\n- Tie-ups with two local schools as temporary shelters\n\n*This draft is being finalised with inputs from last year's response team.*",
                'tags' => ['monsoon', 'relief', 'planning'],
                'published_at' => null,
                'status' => 'draft',
                'featured' => false,
                'views' => 0,
            ],
        ];
    }
}
