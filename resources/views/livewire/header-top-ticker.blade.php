@php $engineers = $this->topEngineers(); @endphp

<div
    class="relative hidden min-w-0 flex-1 cursor-pointer lg:block"
    @click="$flux.modal('top-engineers').show()"
    role="button"
    tabindex="0"
    aria-label="Open the top 100 engineers leaderboard"
    x-data="{
        paused: false,
        init() {
            var track = this.$refs.track;
            if (!track) return;
            var pos = 0;
            var speed = 0.4;
            var self = this;
            function tick() {
                if (!self.paused) {
                    pos -= speed;
                    if (Math.abs(pos) >= track.scrollWidth / 2) pos = 0;
                    track.style.transform = 'translateX(' + pos + 'px)';
                }
                requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
            this.$el.addEventListener('mouseenter', function() { self.paused = true; });
            this.$el.addEventListener('mouseleave', function() { self.paused = false; });
        }
    }"
    style="-webkit-mask-image:linear-gradient(90deg,transparent 0%,black 4%,black 96%,transparent 100%);mask-image:linear-gradient(90deg,transparent 0%,black 4%,black 96%,transparent 100%)"
>
    <div style="overflow:hidden;width:100%">
        <div x-ref="track" style="display:flex;width:max-content">
            @foreach ([0, 1] as $copy)
                <span style="display:flex;align-items:center;gap:0.625rem;padding-right:0.625rem" aria-hidden="{{ $copy === 1 ? 'true' : 'false' }}">
                    @foreach ($engineers as $index => $engineer)
                        <span class="header-avatar-pill" style="position:relative;flex-shrink:0;display:block;border-radius:9999px;padding:2px;border:1.5px solid var(--avatar-pill-border);background:var(--avatar-pill-bg);box-shadow:0 1px 3px var(--avatar-pill-shadow);transition:border-color 0.2s">
                            <flux:avatar :src="$engineer->avatarUrl()" :alt="$engineer->name" circle style="width:26px;height:26px;display:block" />
                            @if (\App\Support\FeatureFlags::publicPresenceEnabled() && $engineer->isOnline())
                                <span style="position:absolute;bottom:0;right:0;width:8px;height:8px;border-radius:50%;border:1.5px solid var(--avatar-pill-border);background:#10b981"></span>
                            @endif
                        </span>
                    @endforeach
                </span>
            @endforeach
        </div>
    </div>
</div>

<style>
:root {
    --avatar-pill-border: #ffffff;
    --avatar-pill-bg: rgba(255,255,255,0.9);
    --avatar-pill-shadow: rgba(0,0,0,0.06);
}
.dark {
    --avatar-pill-border: #27272a;
    --avatar-pill-bg: rgba(39,39,42,0.9);
    --avatar-pill-shadow: rgba(0,0,0,0.3);
}
.header-avatar-pill:hover {
    border-color: var(--color-accent, #3750eb) !important;
}
</style>
