<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'PHP', 'Laravel', 'Livewire', 'JavaScript', 'TypeScript', 'React', 'Vue.js',
            'Python', 'Go', 'Rust', 'Java', 'Ruby', 'C#', 'C++',
            'MySQL', 'PostgreSQL', 'SQLite', 'MongoDB', 'Redis',
            'Docker', 'Kubernetes', 'AWS', 'Google Cloud', 'Azure', 'Terraform',
            'GraphQL', 'REST APIs', 'WebSockets', 'Redis', 'RabbitMQ',
            'Tailwind CSS', 'Alpine.js', 'Vite', 'Node.js', 'Express',
            'Machine Learning', 'DevOps', 'CI/CD', 'Testing', 'Security',
            'Microservices', 'System Design', 'Performance Optimization',
        ];

        $seen = [];

        foreach (array_unique($skills) as $name) {
            $slug = Str::slug($name);

            if (isset($seen[$slug])) {
                continue;
            }

            $seen[$slug] = true;

            Skill::firstOrCreate(
                ['name' => $name],
                ['slug' => $slug],
            );
        }
    }
}
