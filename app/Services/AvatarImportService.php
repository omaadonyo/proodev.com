<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downloads a remote avatar (e.g. a GitHub profile photo found while
 * scouting) into the user's local avatar storage and points avatar_path at
 * it. Fails open: any network or file error simply leaves the current
 * avatar untouched.
 */
class AvatarImportService
{
    public function import(User $user, ?string $avatarUrl): bool
    {
        if (! $avatarUrl) {
            return false;
        }

        try {
            $response = Http::withHeaders(['User-Agent' => 'ProoDev-AvatarImport'])
                ->timeout(15)
                ->get($avatarUrl);

            if ($response->failed()) {
                return false;
            }

            $contentType = (string) $response->header('Content-Type');
            $extension = match (true) {
                Str::contains($contentType, 'jpeg') || Str::contains($contentType, 'jpg') => 'jpg',
                Str::contains($contentType, 'webp') => 'webp',
                Str::contains($contentType, 'png') => 'png',
                default => null,
            };

            if ($extension === null) {
                return false;
            }

            $path = 'avatars/'.$user->id.'-'.Str::lower(Str::random(8)).'.'.$extension;

            Storage::disk('public')->put($path, $response->body());

            if ($user->avatar_path && $user->avatar_path !== $path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->forceFill(['avatar_path' => $path])->save();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}