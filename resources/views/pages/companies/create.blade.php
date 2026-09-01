<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\CompanyMember;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Register Your Company')] class extends Component {
    public string $name = '';

    public function create(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $company = Company::create([
            'owner_id' => auth()->id(),
            'name' => $validated['name'],
            'plan' => CompanyPlan::Trial,
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
        ]);

        CompanyMember::create([
            'company_id' => $company->id,
            'user_id' => auth()->id(),
            'role' => 'owner',
        ]);

        Flux::toast(variant: 'success', text: 'Your company is live on the free plan.');

        $this->redirectRoute('companies.onboarding', $company, navigate: true);
    }

}
?>

<div class="mx-auto w-full max-w-xl">
    <div class="grid gap-6">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-accent/10">
                <flux:icon name="building-office-2" class="size-7 text-accent" />
            </div>
            <flux:heading size="xl">Register your company</flux:heading>
            <flux:text class="mt-2">Get a public company profile and start hiring from evidence-backed developers. Your free plan is active right away.</flux:text>
        </div>

        <form wire:submit="create" class="grid gap-5 ">
            <flux:field>
                <flux:label>Company name</flux:label>
                <flux:input wire:model="name" placeholder="Acme Inc." autofocus />
                <flux:error name="name" />
            </flux:field>

            <div class="grid gap-3">
                <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300">
                    <flux:icon name="check-circle" variant="micro" class="shrink-0" />
                    Free plan, 1 active job. No review needed.
                </div>
                <div class="flex items-center justify-between gap-4">
                    <flux:button type="button" variant="ghost" :href="route('companies.index')" wire:navigate>Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Create company</flux:button>
                </div>
            </div>
        </form>
    </div>
</div>