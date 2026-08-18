@extends(\Wirechat\Wirechat\Facades\Wirechat::currentPanel()->getLayout())

@php
    $chatConversation = auth()->check() && request()->conversation
        ? \Wirechat\Wirechat\Facades\Wirechat::conversationModelClass()::find(request()->conversation)
        : null;
@endphp

@section('content')

    <div class="relative w-full flex min-h-full h-full rounded-lg">
        <aside class="hidden md:grid">
            <div class=" bg-inherit border-r border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]   dark:bg-inherit  relative w-full h-full md:w-[360px] lg:w-[400px] xl:w-[500px]  shrink-0 overflow-y-auto  ">
                <livewire:wirechat.chats :panel="$panel" />
            </div>
        </aside>


        <main class="relative grid w-full grow h-full min-h-min overflow-y-auto" style="contain:content">
            @if ($chatConversation)
                <div class="absolute right-3 top-3 z-20">
                    <livewire:chat-reminder-mute :conversation-id="$chatConversation->getKey()" wire:key="'mute-'.$chatConversation->getKey()" />
                </div>
            @endif
            <livewire:wirechat.chat :panel="$panel" conversation="{{ request()->conversation }}"/>
        </main>

    </div>
@endsection
