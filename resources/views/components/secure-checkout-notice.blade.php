@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'flex items-start gap-2.5 rounded-lg border border-zinc-200 p-3.5 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400 '.$class]) }}>
    <flux:icon name="shield-check" variant="mini" class="mt-0.5 shrink-0 text-emerald-500" />
    <p>
        <span class="font-semibold text-zinc-700 dark:text-zinc-300">Secure checkout.</span>
        ProoDev never stores your credit card or payment details on our platform. Payments are handled directly by our payment providers. Your card and payment info stays with them, never with us.
    </p>
</div>
