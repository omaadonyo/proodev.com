<?php

use App\Livewire\Concerns\ExportsSelectedRows;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\User;
use App\Services\IpCountryResolver;
use App\Services\UserAgentParser;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Analytics')] class extends Component {
    use ExportsSelectedRows;

    public string $newIp = '';
    public string $newIpReason = '';
    public string $logSearch = '';
    public string $sessionSearch = '';

    public function blockIp(string $ip = ''): void
    {
        $ip = trim($ip !== '' ? $ip : $this->newIp);

        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            Flux::toast(variant: 'warning', text: 'Enter a valid IP address.');

            return;
        }

        if (BlockedIp::where('ip_address', $ip)->exists()) {
            Flux::toast(variant: 'warning', text: 'That IP address is already blocked.');

            return;
        }

        BlockedIp::create([
            'ip_address' => $ip,
            'reason' => trim($this->newIpReason) !== '' ? trim($this->newIpReason) : null,
            'blocked_by' => auth()->id(),
        ]);

        $this->newIp = '';
        $this->newIpReason = '';

        unset($this->blockedIps, $this->sessions);

        Flux::toast(variant: 'success', text: "{$ip} has been blocked.");
    }

    public function unblockIp(int $id): void
    {
        BlockedIp::findOrFail($id)->delete();

        unset($this->blockedIps);

        Flux::toast(variant: 'success', text: 'IP address unblocked.');
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'users' => User::count(),
            'online' => DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>', now()->subMinutes(5)->timestamp)
                ->distinct()
                ->count('user_id'),
            'loginsToday' => AuditLog::where('action', 'login')
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
            'sessions' => DB::table('sessions')->count(),
            'blocked' => BlockedIp::count(),
        ];
    }

    #[Computed]
    public function sessions()
    {
        return DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->when(trim($this->sessionSearch) !== '', fn ($query) => $query->where(function ($query) {
                $query->where('sessions.ip_address', 'like', '%'.trim($this->sessionSearch).'%')
                    ->orWhere('users.name', 'like', '%'.trim($this->sessionSearch).'%')
                    ->orWhere('users.email', 'like', '%'.trim($this->sessionSearch).'%');
            }))
            ->orderByDesc('sessions.last_activity')
            ->limit(100)
            ->get([
                'sessions.id as session_id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->map(function ($row) {
                $parsed = UserAgentParser::parse($row->user_agent);

                return [
                    'session_id' => $row->session_id,
                    'user_id' => $row->user_id,
                    'user_name' => $row->user_name,
                    'user_email' => $row->user_email,
                    'ip' => $row->ip_address,
                    'country' => IpCountryResolver::country($row->ip_address),
                    'last_activity' => $row->last_activity,
                    ...$parsed,
                ];
            });
    }

    #[Computed]
    public function usersLog()
    {
        return AuditLog::with('user')
            ->where('action', 'login')
            ->when(trim($this->logSearch) !== '', fn ($query) => $query->where(function ($query) {
                $query->where('ip_address', 'like', '%'.trim($this->logSearch).'%')
                    ->orWhereHas('user', fn ($query) => $query
                        ->where('name', 'like', '%'.trim($this->logSearch).'%')
                        ->orWhere('email', 'like', '%'.trim($this->logSearch).'%'));
            }))
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (AuditLog $log) {
                $device = data_get($log->data, 'device', []);

                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user?->name,
                    'user_email' => $log->user?->email,
                    'ip' => $log->ip_address,
                    'country' => IpCountryResolver::country($log->ip_address),
                    'device' => $device['type'] ?? 'unknown',
                    'os' => $device['os'] ?? 'Unknown',
                    'browser' => $device['browser'] ?? 'Unknown',
                    'created_at' => $log->created_at,
                ];
            });
    }

    #[Computed]
    public function devices(): array
    {
        return $this->sessions->countBy('type')
            ->map(fn ($count) => $count)
            ->sortDesc()
            ->all();
    }

    #[Computed]
    public function countries(): array
    {
        return $this->sessions->countBy('country')
            ->sortDesc()
            ->take(8)
            ->all();
    }

    #[Computed]
    public function browsers(): array
    {
        return $this->sessions->countBy('browser')
            ->sortDesc()
            ->take(6)
            ->all();
    }

    #[Computed]
    public function operatingSystems(): array
    {
        return $this->sessions->countBy('os')
            ->sortDesc()
            ->take(6)
            ->all();
    }

    #[Computed]
    public function blockedIps()
    {
        return BlockedIp::with('blockedBy')->latest()->limit(50)->get();
    }

    public function updatedLogSearch(): void
    {
        $this->selectedIds = [];
    }

    protected function selectableIds(): array
    {
        return collect($this->usersLog)->pluck('id')->toArray();
    }

    protected function exportData(): array
    {
        $rows = collect($this->usersLog)
            ->whereIn('id', $this->selectedIds)
            ->map(fn (array $log) => [
                $log['user_name'] ?? '-',
                $log['user_email'] ?? '',
                $log['country'],
                $log['device'],
                $log['browser'],
                $log['os'],
                $log['ip'] ?? '-',
                $log['created_at']?->toDateTimeString() ?? '',
            ])
            ->values()
            ->all();

        return [
            ['User', 'Email', 'Country', 'Device', 'Browser', 'OS', 'IP address', 'When'],
            $rows,
        ];
    }

    protected function exportTitle(): string
    {
        return 'Login activity';
    }

    protected function exportBasename(): string
    {
        return 'login-activity';
    }
}
?>

<div class="grid gap-6">
    <div>
        <flux:heading size="xl">Analytics</flux:heading>
        <flux:text>User activity, device and country breakdowns, plus IP-level access control.</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->stats['users'] }}</div>
            <div class="text-xs text-zinc-500">Total users</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="flex items-center gap-2 text-2xl font-bold">
                {{ $this->stats['online'] }}
                <span class="size-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <div class="text-xs text-zinc-500">Online now</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->stats['loginsToday'] }}</div>
            <div class="text-xs text-zinc-500">Logins today</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold">{{ $this->stats['sessions'] }}</div>
            <div class="text-xs text-zinc-500">Active sessions</div>
        </div>
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <div class="text-2xl font-bold {{ $this->stats['blocked'] ? 'text-red-500' : '' }}">{{ $this->stats['blocked'] }}</div>
            <div class="text-xs text-zinc-500">Blocked IPs</div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <flux:heading size="sm">Devices</flux:heading>
            <div class="mt-3 grid gap-2 text-sm">
                @forelse ($this->devices as $device => $count)
                    <div class="flex items-center justify-between">
                        <span class="capitalize text-zinc-600 dark:text-zinc-300">{{ $device }}</span>
                        <span class="font-semibold tabular-nums">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No session data yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <flux:heading size="sm">Countries</flux:heading>
            <div class="mt-3 grid gap-2 text-sm">
                @forelse ($this->countries as $country => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $country }}</span>
                        <span class="font-semibold tabular-nums">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No session data yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg bg-zinc-100 p-3 dark:bg-white/5">
            <flux:heading size="sm">Browsers</flux:heading>
            <div class="mt-3 grid gap-2 text-sm">
                @forelse ($this->browsers as $browser => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $browser }}</span>
                        <span class="font-semibold tabular-nums">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">No session data yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Users log</flux:heading>
            <flux:input icon="magnifying-glass" type="search" placeholder="Search users log..." wire:model.live.debounce.300ms="logSearch" class="w-full sm:w-72" />
            @if (count($this->selectedIds) > 0)
                <span class="text-xs font-medium text-accent">{{ count($this->selectedIds) }} selected</span>
                <button type="button" wire:click="exportSelectedPdf" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                    <flux:icon name="document-arrow-down" variant="micro" />
                    PDF
                </button>
                <button type="button" wire:click="exportSelectedExcel" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-zinc-100 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-200 dark:bg-white/10 dark:text-zinc-200 dark:hover:bg-white/15">
                    <flux:icon name="table-cells" variant="micro" />
                    Excel
                </button>
            @endif
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="w-8 px-3 py-2.5 font-medium">
                            <input type="checkbox" wire:click="toggleSelectAll" {{ count($this->selectedIds) === count($this->usersLog) && count($this->usersLog) > 0 ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                        </th>
                        <th class="px-3 py-2.5 font-medium">User</th>
                        <th class="px-3 py-2.5 font-medium">Country</th>
                        <th class="px-3 py-2.5 font-medium">Device</th>
                        <th class="px-3 py-2.5 font-medium">Browser</th>
                        <th class="px-3 py-2.5 font-medium">OS</th>
                        <th class="px-3 py-2.5 font-medium">IP address</th>
                        <th class="px-3 py-2.5 font-medium">When</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->usersLog as $log)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800 {{ in_array($log['id'], $this->selectedIds) ? 'bg-accent/5' : '' }}">
                            <td class="px-3 py-2.5">
                                <input type="checkbox" wire:click="toggleSelect({{ $log['id'] }})" {{ in_array($log['id'], $this->selectedIds) ? 'checked' : '' }} class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-600" />
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="font-medium">{{ $log['user_name'] ?? '-' }}</span>
                                <span class="block text-xs text-zinc-500">{{ $log['user_email'] ?? '' }}</span>
                            </td>
                            <td class="px-3 py-2.5">{{ $log['country'] }}</td>
                            <td class="px-3 py-2.5 capitalize">{{ $log['device'] }}</td>
                            <td class="px-3 py-2.5">{{ $log['browser'] }}</td>
                            <td class="px-3 py-2.5">{{ $log['os'] }}</td>
                            <td class="px-3 py-2.5 font-mono text-xs">{{ $log['ip'] ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ $log['created_at']?->diffForHumans() }}</td>
                            <td class="px-3 py-2.5">
                                @if ($log['ip'])
                                    <flux:button size="sm" variant="subtle" wire:click="blockIp('{{ $log['ip'] }}')">
                                        Block
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No login events recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Active sessions</flux:heading>
            <flux:input icon="magnifying-glass" type="search" placeholder="Search sessions..." wire:model.live.debounce.300ms="sessionSearch" class="w-full sm:w-72" />
        </div>

        <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700">
                        <th class="px-3 py-2.5 font-medium">User</th>
                        <th class="px-3 py-2.5 font-medium">Country</th>
                        <th class="px-3 py-2.5 font-medium">Device</th>
                        <th class="px-3 py-2.5 font-medium">Browser</th>
                        <th class="px-3 py-2.5 font-medium">OS</th>
                        <th class="px-3 py-2.5 font-medium">IP address</th>
                        <th class="px-3 py-2.5 font-medium">Last activity</th>
                        <th class="px-3 py-2.5 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->sessions as $session)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                            <td class="px-3 py-2.5">
                                <span class="font-medium">{{ $session['user_name'] ?? 'Guest' }}</span>
                                <span class="block text-xs text-zinc-500">{{ $session['user_email'] ?? '' }}</span>
                            </td>
                            <td class="px-3 py-2.5">{{ $session['country'] }}</td>
                            <td class="px-3 py-2.5 capitalize">{{ $session['type'] }}</td>
                            <td class="px-3 py-2.5">{{ $session['browser'] }}</td>
                            <td class="px-3 py-2.5">{{ $session['os'] }}</td>
                            <td class="px-3 py-2.5 font-mono text-xs">{{ $session['ip'] }}</td>
                            <td class="px-3 py-2.5 text-zinc-500">{{ \Illuminate\Support\Carbon::createFromTimestamp($session['last_activity'])->diffForHumans() }}</td>
                            <td class="px-3 py-2.5">
                                <flux:button size="sm" variant="subtle" wire:click="blockIp('{{ $session['ip'] }}')">
                                    Block
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-zinc-500">
                                No active sessions.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="sm">Blocked IP addresses</flux:heading>
            <flux:text>Blocked IPs are rejected by middleware before any request is served.</flux:text>
        </div>

        <div class="mt-4 rounded-md bg-zinc-100 p-3 dark:bg-white/5">
            <div class="grid gap-3 md:grid-cols-3">
                <flux:field>
                    <flux:label>IP address</flux:label>
                    <flux:input wire:model="newIp" placeholder="203.0.113.7" />
                </flux:field>
                <flux:field>
                    <flux:label>Reason (optional)</flux:label>
                    <flux:input wire:model="newIpReason" placeholder="Abusive traffic" />
                </flux:field>
                <div class="flex items-end">
                    <flux:button variant="primary" wire:click="blockIp" class="w-full">Block IP</flux:button>
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-2">
            @forelse ($this->blockedIps as $blocked)
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-zinc-100 p-3 text-sm dark:bg-white/5">
                    <span class="font-mono text-xs font-medium">{{ $blocked->ip_address }}</span>
                    <span class="text-zinc-500">
                        {{ $blocked->reason ?: 'No reason given' }}
                        @if ($blocked->blockedBy)
                            · by {{ $blocked->blockedBy->name }}
                        @endif
                    </span>
                    <span class="text-xs text-zinc-500">{{ $blocked->created_at->diffForHumans() }}</span>
                    <span class="ms-auto">
                        <flux:button size="sm" variant="subtle" wire:click="unblockIp({{ $blocked->id }})">Unblock</flux:button>
                    </span>
                </div>
            @empty
                <p class="text-sm text-zinc-500">No blocked IP addresses.</p>
            @endforelse
        </div>
    </div>
</div>
