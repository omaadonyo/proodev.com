@props(['text' => ''])

@php
    $html = \App\Support\Markdown::render($text);
@endphp

@if ($html !== '')
    <div
        {{ $attributes->class([
            'text-sm leading-relaxed text-zinc-700 dark:text-zinc-300',
            '[&_p]:my-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0',
            '[&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5',
            '[&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5',
            '[&_li]:my-0.5',
            '[&_h1]:my-3 [&_h2]:my-3 [&_h3]:my-2 [&_h1]:text-lg [&_h2]:text-base [&_h3]:text-sm [&_h1]:font-bold [&_h2]:font-semibold [&_h3]:font-semibold',
            '[&_a]:text-accent [&_a]:underline [&_a]:underline-offset-2 [&_a:hover]:opacity-80',
            '[&_code]:rounded [&_code]:bg-zinc-100 [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[0.85em] dark:[&_code]:bg-zinc-800',
            '[&_pre]:my-3 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-zinc-950 [&_pre]:p-3 [&_pre]:text-xs dark:[&_pre]:bg-zinc-900',
            '[&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:text-zinc-100',
            '[&_blockquote]:my-3 [&_blockquote]:border-s-2 [&_blockquote]:border-zinc-300 [&_blockquote]:ps-3 [&_blockquote]:text-zinc-500 dark:[&_blockquote]:border-zinc-700',
            '[&_table]:my-3 [&_table]:w-full [&_table]:border-collapse [&_table]:text-xs',
            '[&_th]:border [&_th]:border-zinc-200 [&_th]:bg-zinc-50 [&_th]:px-2 [&_th]:py-1.5 [&_th]:text-start [&_th]:font-semibold dark:[&_th]:border-zinc-700 dark:[&_th]:bg-zinc-900',
            '[&_td]:border [&_td]:border-zinc-200 [&_td]:px-2 [&_td]:py-1.5 dark:[&_td]:border-zinc-700',
            '[&_img]:my-3 [&_img]:max-w-full [&_img]:rounded-lg',
        ]) }}
    >{!! $html !!}</div>
@endif
