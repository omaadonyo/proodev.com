<?php

use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    public $avatar = null;

    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $headline = '';

    public string $bio = '';

    public string $location = '';

    public string $github_url = '';

    public string $website_url = '';

    public string $linkedin_url = '';

    public bool $public_passport = true;

    public string $short_domain = '';

    public bool $email_job_offers = true;

    public bool $email_new_chats = true;

    public bool $email_scans_evidence = true;

    public bool $email_transactions = true;

    public bool $notify_chats = true;

    public bool $notify_mentions = true;

    public bool $notify_weekly_reports = true;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username ?? '';
        $this->headline = $user->headline ?? '';
        $this->bio = $user->bio ?? '';
        $this->location = $user->location ?? '';
        $this->github_url = $user->github_url ?? '';
        $this->website_url = $user->website_url ?? '';
        $this->linkedin_url = $user->linkedin_url ?? '';
        $this->public_passport = (bool) $user->public_passport;
        $this->short_domain = $user->short_domain ?? '';
        $this->email_job_offers = $user->wantsEmail('job_offers');
        $this->email_new_chats = $user->wantsEmail('new_chats');
        $this->email_scans_evidence = $user->wantsEmail('scans_evidence');
        $this->email_transactions = $user->wantsEmail('transactions');
        $this->notify_chats = $user->wantsNotification('chats');
        $this->notify_mentions = $user->wantsNotification('mentions');
        $this->notify_weekly_reports = $user->wantsNotification('weekly_reports');
    }

    /**
     * Save the user's email notification preferences.
     */
    public function updateEmailPreferences(): void
    {
        $user = Auth::user();

        $preferences = array_merge($user->preferences ?? [], [
            'email_job_offers' => (bool) $this->email_job_offers,
            'email_new_chats' => (bool) $this->email_new_chats,
            'email_scans_evidence' => (bool) $this->email_scans_evidence,
            'email_transactions' => (bool) $this->email_transactions,
        ]);

        $user->forceFill(['preferences' => $preferences])->save();

        Flux::toast(variant: 'success', text: __('Email preferences updated.'));
    }

    /**
     * Save the user's in-app (database) notification preferences.
     */
    public function updateNotificationPreferences(): void
    {
        $user = Auth::user();

        $preferences = array_merge($user->preferences ?? [], [
            'notify_chats' => (bool) $this->notify_chats,
            'notify_mentions' => (bool) $this->notify_mentions,
            'notify_weekly_reports' => (bool) $this->notify_weekly_reports,
        ]);

        $user->forceFill(['preferences' => $preferences])->save();

        Flux::toast(variant: 'success', text: __('Notification preferences updated.'));
    }

    /**
     * Upload and store a new profile avatar.
     */
    public function saveAvatar(): void
    {
        $this->validate(['avatar' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->avatar->store('avatars', 'public');

        $user->forceFill(['avatar_path' => $path])->save();

        $this->avatar = null;

        Flux::toast(variant: 'success', text: __('Avatar updated.'));
    }

    /**
     * Remove the uploaded profile avatar.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();

        $this->avatar = null;

        Flux::toast(variant: 'success', text: __('Avatar removed.'));
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->passportProfileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Update the short shareable DevID link for a verified developer.
     */
    public function updateShortDomain(): void
    {
        $user = Auth::user();

        abort_unless($user->isVerified(), 403);

        $validated = $this->validate([
            'short_domain' => [
                'required',
                'string',
                'regex:/^[a-zA-Z0-9\-_]+$/',
                'min:3',
                'max:30',
                Rule::unique('users', 'short_domain')->ignore($user->id),
            ],
        ]);

        $user->forceFill(['short_domain' => strtolower($validated['short_domain'])])->save();

        Flux::toast(variant: 'success', text: __('Short link updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    #[Computed]
    public function shortLink(): ?string
    {
        return Auth::user()->shortLink();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name, email and DevID details')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="flex items-center gap-5 rounded-xl border border-zinc-200 p-5 dark:border-white/10">
                <div class="relative shrink-0">
                    <div class="size-20 overflow-hidden rounded-full border border-zinc-200 bg-zinc-100 dark:border-white/10 dark:bg-zinc-900">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Avatar preview" class="size-full object-cover" />
                        @else
                            <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" class="size-full object-cover" />
                        @endif
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <flux:heading size="sm">{{ __('Profile photo') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Upload a square JPG, PNG or WebP up to 2 MB.') }}</flux:text>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <label class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg bg-zinc-900 px-4 text-sm font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <flux:icon name="photo" variant="micro" class="size-4" />
                            {{ __('Upload photo') }}
                            <input type="file" wire:model="avatar" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                        </label>
                        @if ($avatar)
                            <flux:button variant="primary" type="button" wire:click="saveAvatar" class="h-9" data-test="save-avatar-button">
                                {{ __('Save photo') }}
                            </flux:button>
                        @endif
                        @if (auth()->user()->avatar_path)
                            <flux:button variant="subtle" type="button" wire:click="removeAvatar" wire:confirm="Remove your profile photo?" class="h-9">
                                {{ __('Remove') }}
                            </flux:button>
                        @endif
                    </div>
                    @error('avatar')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                <flux:input wire:model="username" :label="__('Username')" type="text" required autocomplete="username" hint="Used in your public DevID URL." />
            </div>

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="border-t border-zinc-100 pt-6 dark:border-white/10">
                <flux:heading size="sm">{{ __('DevID details') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Shown publicly on your DevID.') }}</flux:text>

                <div class="mt-4 grid gap-6">
                    <flux:input wire:model="headline" :label="__('Headline')" type="text" maxlength="120" placeholder="Full-stack engineer building real-time products" />

                    <flux:textarea wire:model="bio" :label="__('Bio')" rows="4" maxlength="1000" placeholder="A short summary of who you are and what you build." />

                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:input wire:model="location" :label="__('Location')" type="text" maxlength="120" placeholder="Berlin, Germany" />

                        <flux:input wire:model="github_url" :label="__('GitHub URL')" type="url" placeholder="https://github.com/you" />

                        <flux:input wire:model="website_url" :label="__('Website URL')" type="url" placeholder="https://your-site.dev" />

                        <flux:input wire:model="linkedin_url" :label="__('LinkedIn URL')" type="url" placeholder="https://linkedin.com/in/you" />
                    </div>

                    <flux:switch wire:model="public_passport" label="Make my DevID public" description="Anyone with the link can see your DevID, evidence, projects and vouches." />
                </div>
            </div>

            @if (auth()->user()->isVerified())
                <div class="rounded-xl border border-emerald-300/50 bg-emerald-50/40 p-4 dark:border-emerald-400/20 dark:bg-emerald-400/5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <flux:icon name="link" class="size-4 text-emerald-600 dark:text-emerald-400" />
                            <flux:heading size="sm">{{ __('Short DevID link') }}</flux:heading>
                        </div>
                        @if ($this->shortLink)
                            <div
                                x-data="{ copied: false }"
                                class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 dark:border-zinc-700 dark:bg-zinc-900"
                            >
                                <a href="{{ $this->shortLink }}" wire:navigate class="truncate font-mono text-xs font-semibold text-accent hover:underline">
                                    {{ $this->shortLink }}
                                </a>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ $this->shortLink }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                    class="inline-flex shrink-0 items-center gap-1 rounded-md bg-zinc-900 px-2.5 py-1 text-[11px] font-semibold text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
                                >
                                    <flux:icon name="clipboard" variant="micro" class="size-3" />
                                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                    <flux:text class="mt-1">Choose a short name to share. Anyone with the link opens your DevID.</flux:text>

                    <div class="mt-3 flex items-end gap-2">
                        <div class="flex-1">
                            <flux:field>
                                <flux:label>{{ url('/p') }}/<span class="text-zinc-400">your-name</span></flux:label>
                                <flux:input wire:model="short_domain" placeholder="your-name" />
                                <flux:error name="short_domain" />
                            </flux:field>
                        </div>
                        <flux:button type="button" variant="primary" wire:click="updateShortDomain" class="h-10">
                            {{ __('Save link') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

        <div class="mt-8 rounded-xl border border-zinc-200 p-5 dark:border-white/10">
            <flux:heading size="sm">{{ __('Email preferences') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Choose which emails you want to receive. You can change these anytime.') }}</flux:text>

            <div class="mt-4 grid gap-5">
                <flux:switch
                    wire:model="email_job_offers"
                    label="{{ __('New job offers') }}"
                    description="{{ __('Email me when a new job is posted that may match my profile.') }}"
                />

                <flux:switch
                    wire:model="email_new_chats"
                    label="{{ __('New chats') }}"
                    description="{{ __('Email me a reminder to reply when someone messages me.') }}"
                />

                <flux:switch
                    wire:model="email_scans_evidence"
                    label="{{ __('Scans & evidence') }}"
                    description="{{ __('Email me when a scan finishes or new evidence is added to my DevID.') }}"
                />

                <flux:switch
                    wire:model="email_transactions"
                    label="{{ __('Transactions & payments') }}"
                    description="{{ __('Email me confirmations and updates about my payments, invoices and purchases.') }}"
                />
            </div>

            <div class="mt-5 flex justify-end">
                <flux:button variant="primary" type="button" wire:click="updateEmailPreferences" data-test="update-email-preferences-button">
                    {{ __('Save email preferences') }}
                </flux:button>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-zinc-200 p-5 dark:border-white/10">
            <flux:heading size="sm">{{ __('Notification preferences') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Choose which in-app notifications you see in the notification bell.') }}</flux:text>

            <div class="mt-4 grid gap-5">
                <flux:switch
                    wire:model="notify_chats"
                    label="{{ __('New chat messages') }}"
                    description="{{ __('Notify me in-app when someone sends me a message.') }}"
                />

                <flux:switch
                    wire:model="notify_mentions"
                    label="{{ __('Mentions') }}"
                    description="{{ __('Notify me in-app when someone mentions me in a comment.') }}"
                />

                <flux:switch
                    wire:model="notify_weekly_reports"
                    label="{{ __('Weekly reports') }}"
                    description="{{ __('Notify me in-app when my weekly engineering report is ready.') }}"
                />
            </div>

            <div class="mt-5 flex justify-end">
                <flux:button variant="primary" type="button" wire:click="updateNotificationPreferences" data-test="update-notification-preferences-button">
                    {{ __('Save notification preferences') }}
                </flux:button>
            </div>
        </div>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
