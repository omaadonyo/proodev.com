<x-layouts::auth :title="__('Register')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-auth-social-providers />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6" x-data="{ role: '{{ request('role') === 'company' ? 'company' : 'developer' }}' }">
            @csrf

            <!-- Account type -->
            <flux:radio.group variant="segmented" x-model="role" class="w-full">
                <flux:radio value="developer" icon="code-bracket" class="flex-1">{{ __('Developer') }}</flux:radio>
                <flux:radio value="company" icon="building-office-2" class="flex-1">{{ __('Company') }}</flux:radio>
            </flux:radio.group>

            <input type="hidden" name="role" value="developer" x-bind:value="role" />

            <!-- Company name -->
            <div x-show="role === 'company'" x-cloak>
                <flux:input
                    name="company_name"
                    :label="__('Company name')"
                    :value="old('company_name')"
                    type="text"
                    autocomplete="organization"
                    placeholder="Acme Inc."
                    description="{{ __('Your company is active right away on the free plan.') }}"
                />
            </div>

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
