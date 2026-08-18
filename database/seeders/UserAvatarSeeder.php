<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Gives every user a real, locally-served profile photo so the platform never
 * renders initials-only avatars. Portraits are pulled once from randomuser.me
 * (CC-licensed stock portraits), cached under storage/app/public/avatars/seed
 * and linked via the user's avatar_path — so a user-uploaded photo always wins
 * and there is no runtime dependency on an external image host.
 */
class UserAvatarSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereNull('avatar_path')
            ->orderBy('id')
            ->get()
            ->each(fn (User $user) => $this->assignPortrait($user));
    }

    private function assignPortrait(User $user): void
    {
        $disk = Storage::disk('public');

        $path = 'avatars/seed/'.$user->id.'.jpg';

        if (! $disk->exists($path)) {
            $gender = (crc32((string) $user->id) % 2) === 0 ? 'men' : 'women';
            $index = ($user->id % 99) + 1;

            try {
                $response = Http::timeout(15)->get("https://randomuser.me/api/portraits/{$gender}/{$index}.jpg");

                if (! $response->successful()) {
                    return;
                }

                $disk->put($path, $response->body());
            } catch (\Throwable) {
                return; // offline — keep the initials fallback for this run
            }
        }

        $user->forceFill(['avatar_path' => $path])->saveQuietly();
    }
}
