<div>
    <flux:modal :name="'vouch-'.$userId" class="w-full max-w-md overflow-hidden">
        <form wire:submit="submit">
            <div class="space-y-4 p-6">
                <div>
                    <flux:heading size="lg">Vouch for {{ $this->user->name }}</flux:heading>
                    <flux:text>A vouch means: "I have technical confidence in this person's abilities."</flux:text>
                </div>

                <flux:field>
                    <flux:label>Vouch type</flux:label>
                    <x-searchable-select wire:model="form.type" required>
                        @foreach (\App\Enums\VouchType::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-searchable-select>
                </flux:field>

                @if (in_array($this->form->type, ['skill'], true))
                    <flux:field>
                        <flux:label>Skill</flux:label>
                        <x-searchable-select wire:model="form.skillId">
                            <option value="">Select a skill</option>
                            @foreach ($this->skills as $skill)
                                <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                            @endforeach
                        </x-searchable-select>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Why are you vouching?</flux:label>
                    <flux:textarea wire:model="form.message" rows="3" placeholder="Share the concrete engineering evidence…" />
                </flux:field>

                <div class="rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-900">
                    You have <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ auth()->user()->vouch_credits }}</span> vouch credit(s). Vouches cannot be purchased — they are earned through verified contributions.
                </div>

                <div class="flex justify-end gap-2">
                    <flux:button type="button" variant="ghost" @click="$flux.modal('vouch-{{ $userId }}').close()">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">Send vouch</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:button variant="primary" @click="$flux.modal('vouch-{{ $userId }}').open()">
        <flux:icon name="shield-check" variant="micro" />
        Vouch
    </flux:button>
</div>
