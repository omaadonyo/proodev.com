<x-layouts::app :title="'Checkout'">
    <div class="mx-auto w-full max-w-2xl">
        <div>
            <flux:heading size="lg">Complete your checkout</flux:heading>
            <flux:text>
                #{{ $payment->id }} · {{ $payment->purpose->label() }} · {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}
            </flux:text>

            <div class="mt-6 grid gap-3 text-sm">
                <div class="flex justify-between border-b border-zinc-200 pb-2 dark:border-zinc-700">
                    <span class="text-zinc-500">Method</span>
                    <span class="font-medium">{{ $payment->payment_method?->label() ?? 'Manual' }}</span>
                </div>
                <div class="flex justify-between border-b border-zinc-200 pb-2 dark:border-zinc-700">
                    <span class="text-zinc-500">Reference</span>
                    <span class="font-mono font-medium">{{ $payment->gateway_reference ?? $payment->reference ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-zinc-200 pb-2 dark:border-zinc-700">
                    <span class="text-zinc-500">Status</span>
                    <flux:badge size="sm" inset="top bottom" color="amber">Awaiting payment</flux:badge>
                </div>
            </div>

            <p class="mt-6 text-sm text-zinc-500">
                This build is running without live gateway credentials, so the payment is confirmed through a simulated success below. Once real credentials are configured, buyers are redirected to the gateway instead.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <flux:button variant="primary" :href="route('payments.simulate', $payment)">
                    Simulate payment success
                </flux:button>
                <flux:button variant="subtle" :href="route('home')">
                    Cancel
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts::app>
