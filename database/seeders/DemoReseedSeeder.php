<?php

namespace Database\Seeders;

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Models\Skill;
use App\Models\User;
use App\Services\TimelineService;
use Database\Seeders\Concerns\SeedsDeveloperContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Rebuilds a clean, realistic developer base of 50 engineers from around the
 * world, each with skills, XP, streaks, a project, journal entries, vouches,
 * verifications and a cached portrait. Used by the admin system reset.
 */
class DemoReseedSeeder extends Seeder
{
    use SeedsDeveloperContent;

    public const DEFAULT_COUNT = 50;

    /**
     * @var array<int, array{name: string, username: string, headline: string, location: string, timezone: string}>
     */
    protected array $engineers = [
        ['name' => 'Amara Okafor', 'username' => 'amara-okafor', 'headline' => 'Backend engineer · PHP & distributed systems', 'location' => 'Lagos, Nigeria', 'timezone' => 'Africa/Lagos'],
        ['name' => 'Kwame Mensah', 'username' => 'kwame-mensah', 'headline' => 'Platform engineer · Kubernetes & Go', 'location' => 'Accra, Ghana', 'timezone' => 'Africa/Accra'],
        ['name' => 'Wanjiku Njeri', 'username' => 'wanjiku-njeri', 'headline' => 'Frontend architect · TypeScript & React', 'location' => 'Nairobi, Kenya', 'timezone' => 'Africa/Nairobi'],
        ['name' => 'Thabo Nkosi', 'username' => 'thabo-nkosi', 'headline' => 'DevOps engineer · Terraform & AWS', 'location' => 'Johannesburg, South Africa', 'timezone' => 'Africa/Johannesburg'],
        ['name' => 'Omar Haddad', 'username' => 'omar-haddad', 'headline' => 'Full-stack engineer · Laravel + Vue', 'location' => 'Cairo, Egypt', 'timezone' => 'Africa/Cairo'],
        ['name' => 'Layla Al-Rashid', 'username' => 'layla-alrashid', 'headline' => 'Data engineer · Python & Spark', 'location' => 'Dubai, UAE', 'timezone' => 'Asia/Dubai'],
        ['name' => 'Yael Cohen', 'username' => 'yael-cohen', 'headline' => 'Security engineer · application security', 'location' => 'Tel Aviv, Israel', 'timezone' => 'Asia/Jerusalem'],
        ['name' => 'Ngozi Eze', 'username' => 'ngozi-eze', 'headline' => 'Mobile engineer · Flutter & Firebase', 'location' => 'Abuja, Nigeria', 'timezone' => 'Africa/Lagos'],
        ['name' => 'Akira Tanaka', 'username' => 'akira-tanaka', 'headline' => 'Backend engineer · Rust & microservices', 'location' => 'Tokyo, Japan', 'timezone' => 'Asia/Tokyo'],
        ['name' => 'Min-jun Park', 'username' => 'minjun-park', 'headline' => 'ML engineer · Python & PyTorch', 'location' => 'Seoul, South Korea', 'timezone' => 'Asia/Seoul'],
        ['name' => 'Wei Chen', 'username' => 'wei-chen', 'headline' => 'Platform engineer · Go & Kubernetes', 'location' => 'Singapore', 'timezone' => 'Asia/Singapore'],
        ['name' => 'Priya Sharma', 'username' => 'priya-sharma', 'headline' => 'Full-stack engineer · Laravel + Livewire', 'location' => 'Bengaluru, India', 'timezone' => 'Asia/Kolkata'],
        ['name' => 'Arjun Patel', 'username' => 'arjun-patel', 'headline' => 'Database engineer · PostgreSQL & Redis', 'location' => 'Mumbai, India', 'timezone' => 'Asia/Kolkata'],
        ['name' => 'Mei Lin', 'username' => 'mei-lin', 'headline' => 'Frontend architect · TypeScript & Vue', 'location' => 'Taipei, Taiwan', 'timezone' => 'Asia/Taipei'],
        ['name' => 'Ravi Fernando', 'username' => 'ravi-fernando', 'headline' => 'DevOps engineer · CI/CD & observability', 'location' => 'Colombo, Sri Lanka', 'timezone' => 'Asia/Colombo'],
        ['name' => 'Dinh Nguyen', 'username' => 'dinh-nguyen', 'headline' => 'Full-stack developer · PHP + React', 'location' => 'Ho Chi Minh City, Vietnam', 'timezone' => 'Asia/Ho_Chi_Minh'],
        ['name' => 'Sakura Yamamoto', 'username' => 'sakura-yamamoto', 'headline' => 'Systems engineer · networking', 'location' => 'Osaka, Japan', 'timezone' => 'Asia/Tokyo'],
        ['name' => 'Ahmad Faisal', 'username' => 'ahmad-faisal', 'headline' => 'Backend engineer · Node & Express', 'location' => 'Karachi, Pakistan', 'timezone' => 'Asia/Karachi'],
        ['name' => 'Siti Rahayu', 'username' => 'siti-rahayu', 'headline' => 'Data scientist · machine learning', 'location' => 'Jakarta, Indonesia', 'timezone' => 'Asia/Jakarta'],
        ['name' => 'Chaiwat Srisuwan', 'username' => 'chaiwat-srisuwan', 'headline' => 'Mobile engineer · Kotlin & Android', 'location' => 'Bangkok, Thailand', 'timezone' => 'Asia/Bangkok'],
        ['name' => 'Hana Suzuki', 'username' => 'hana-suzuki', 'headline' => 'Frontend developer · React & TypeScript', 'location' => 'Kyoto, Japan', 'timezone' => 'Asia/Tokyo'],
        ['name' => 'Lukas Weber', 'username' => 'lukas-weber', 'headline' => 'Backend engineer · PHP & distributed systems', 'location' => 'Berlin, Germany', 'timezone' => 'Europe/Berlin'],
        ['name' => 'Emma Dubois', 'username' => 'emma-dubois', 'headline' => 'Frontend architect · TypeScript & Vue', 'location' => 'Paris, France', 'timezone' => 'Europe/Paris'],
        ['name' => 'Sofia Rossi', 'username' => 'sofia-rossi', 'headline' => 'Full-stack engineer · Laravel + Livewire', 'location' => 'Milan, Italy', 'timezone' => 'Europe/Rome'],
        ['name' => 'Ole Hansen', 'username' => 'ole-hansen', 'headline' => 'Platform engineer · Kubernetes & Go', 'location' => 'Copenhagen, Denmark', 'timezone' => 'Europe/Copenhagen'],
        ['name' => 'Elena García', 'username' => 'elena-garcia', 'headline' => 'ML engineer · Python & MLOps', 'location' => 'Madrid, Spain', 'timezone' => 'Europe/Madrid'],
        ['name' => 'João Santos', 'username' => 'joao-santos', 'headline' => 'DevOps engineer · CI/CD & observability', 'location' => 'Lisbon, Portugal', 'timezone' => 'Europe/Lisbon'],
        ['name' => 'Anna Kovács', 'username' => 'anna-kovacs', 'headline' => 'Database engineer · PostgreSQL & Redis', 'location' => 'Budapest, Hungary', 'timezone' => 'Europe/Budapest'],
        ['name' => 'Piotr Nowak', 'username' => 'piotr-nowak', 'headline' => 'Security engineer · application security', 'location' => 'Warsaw, Poland', 'timezone' => 'Europe/Warsaw'],
        ['name' => 'Ingrid Johansson', 'username' => 'ingrid-johansson', 'headline' => 'Systems engineer · Rust & networking', 'location' => 'Stockholm, Sweden', 'timezone' => 'Europe/Stockholm'],
        ['name' => 'Tomáš Novák', 'username' => 'tomas-novak', 'headline' => 'Mobile engineer · cross-platform', 'location' => 'Prague, Czech Republic', 'timezone' => 'Europe/Prague'],
        ['name' => 'Fatima Al-Sayed', 'username' => 'fatima-alsayed', 'headline' => 'Full-stack developer · PHP + Vue', 'location' => 'Amman, Jordan', 'timezone' => 'Asia/Amman'],
        ['name' => 'Lucas Almeida', 'username' => 'lucas-almeida', 'headline' => 'Backend engineer · PHP & distributed systems', 'location' => 'São Paulo, Brazil', 'timezone' => 'America/Sao_Paulo'],
        ['name' => 'Valentina Gómez', 'username' => 'valentina-gomez', 'headline' => 'Frontend architect · TypeScript & React', 'location' => 'Bogotá, Colombia', 'timezone' => 'America/Bogota'],
        ['name' => 'Mateo Fernández', 'username' => 'mateo-fernandez', 'headline' => 'Data engineer · Python & Airflow', 'location' => 'Buenos Aires, Argentina', 'timezone' => 'America/Argentina/Buenos_Aires'],
        ['name' => 'Camila Ruiz', 'username' => 'camila-ruiz', 'headline' => 'DevOps engineer · AWS & Terraform', 'location' => 'Santiago, Chile', 'timezone' => 'America/Santiago'],
        ['name' => 'Diego Morales', 'username' => 'diego-morales', 'headline' => 'Full-stack engineer · Laravel + Livewire', 'location' => 'Lima, Peru', 'timezone' => 'America/Lima'],
        ['name' => 'Olivia Tremblay', 'username' => 'olivia-tremblay', 'headline' => 'ML engineer · Python & PyTorch', 'location' => 'Montreal, Canada', 'timezone' => 'America/Toronto'],
        ['name' => 'James Mitchell', 'username' => 'james-mitchell', 'headline' => 'Platform engineer · Go & Kubernetes', 'location' => 'Austin, TX', 'timezone' => 'America/Chicago'],
        ['name' => 'Sarah Johnson', 'username' => 'sarah-johnson', 'headline' => 'Frontend developer · React & TypeScript', 'location' => 'New York, NY', 'timezone' => 'America/New_York'],
        ['name' => 'Emily Brown', 'username' => 'emily-brown', 'headline' => 'Backend engineer · Ruby & Rails', 'location' => 'San Francisco, CA', 'timezone' => 'America/Los_Angeles'],
        ['name' => 'Carlos Mendoza', 'username' => 'carlos-mendoza', 'headline' => 'Systems engineer · networking', 'location' => 'Mexico City, Mexico', 'timezone' => 'America/Mexico_City'],
        ['name' => 'Isabella Costa', 'username' => 'isabella-costa', 'headline' => 'Mobile engineer · Flutter & Firebase', 'location' => 'Rio de Janeiro, Brazil', 'timezone' => 'America/Sao_Paulo'],
        ['name' => 'Jack Thompson', 'username' => 'jack-thompson', 'headline' => 'Full-stack engineer · Laravel + Vue', 'location' => 'Sydney, Australia', 'timezone' => 'Australia/Sydney'],
        ['name' => 'Charlotte Wilson', 'username' => 'charlotte-wilson', 'headline' => 'Data scientist · machine learning', 'location' => 'Melbourne, Australia', 'timezone' => 'Australia/Melbourne'],
        ['name' => 'Hine Moana', 'username' => 'hine-moana', 'headline' => 'Frontend architect · TypeScript & Vue', 'location' => 'Auckland, New Zealand', 'timezone' => 'Pacific/Auckland'],
        ['name' => 'Oliver Davies', 'username' => 'oliver-davies', 'headline' => 'DevOps engineer · CI/CD & observability', 'location' => 'London, UK', 'timezone' => 'Europe/London'],
        ['name' => 'Aoife O\'Brien', 'username' => 'aoife-obrien', 'headline' => 'Security engineer · application security', 'location' => 'Dublin, Ireland', 'timezone' => 'Europe/Dublin'],
        ['name' => 'Erik Nilsson', 'username' => 'erik-nilsson', 'headline' => 'Backend engineer · PHP & PostgreSQL', 'location' => 'Oslo, Norway', 'timezone' => 'Europe/Oslo'],
        ['name' => 'Laura Heikkilä', 'username' => 'laura-heikkila', 'headline' => 'Full-stack engineer · Laravel + Livewire', 'location' => 'Helsinki, Finland', 'timezone' => 'Europe/Helsinki'],
    ];

    public function run(): void
    {
        $skills = Skill::pluck('id', 'slug')->all();

        $profiles = [];

        foreach ($this->engineers as $index => $engineer) {
            $user = User::firstOrCreate(
                ['email' => $engineer['username'].'@engineeringos.test'],
                array_merge([
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'bio' => 'Building things, breaking things, and writing about both.',
                    'public_passport' => true,
                ], $engineer),
            );

            $this->assignCachedAvatar($user, $index);
            $this->attachSkills($user, $skills, $index);
            $this->awardProfileGrowth($user, $index);

            app(TimelineService::class)->record(
                $user,
                TimelineEventType::Joined,
                'Joined ProoDev',
                'Welcome to the platform where growth is public.',
                [],
                null,
                Visibility::Public,
                now()->subDays(rand(10, 90)),
            );

            $profiles[] = $user;
        }

        $admin = User::where('email', 'adonyo@proodev.com')->first();

        $this->seedProjects($admin, $profiles);
        $this->seedJournal($profiles);
        $this->seedVouches($profiles);
        $this->seedVerifications($profiles);
        $this->seedReports($profiles);
        $this->seedWeeklyReports($profiles);
        $this->recalculateReputations($profiles);
    }
}
