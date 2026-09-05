<?php

declare(strict_types=1);
$content = file_get_contents('resources/views/livewire/admin/finance/analytics-dashboard.blade.php');

$search = <<<'HTML'
    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @if(config('marketplace.features.orders_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">GMV</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['gmv'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Orders</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_orders'] ?? 0 }}</span>
                </div>
            </flux:card>
        @endif
        @if(config('marketplace.features.payouts_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Payouts Disbursed</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_payouts_disbursed'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
        @endif
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Sellers</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_active_sellers'] ?? 0 }}</span>
            </div>
        </flux:card>
        @if(config('marketplace.features.reviews_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg Rating</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                        @if (($metrics['platform_average_rating'] ?? 0) > 0)
                            {{ number_format((float) $metrics['platform_average_rating'], 2) }} <span class="text-base font-normal text-zinc-500">/ 5.00</span>
                        @else
                            —
                        @endif
                    </span>
                </div>
            </flux:card>
        @endif
    </div>
HTML;

$replace = <<<'HTML'
    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @if(config('marketplace.features.orders_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">GMV</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['gmv'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Orders</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_orders'] ?? 0 }}</span>
                </div>
            </flux:card>
        @endif
        @if(config('marketplace.features.payouts_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Payouts Disbursed</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_payouts_disbursed'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
        @endif
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Sellers</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_active_sellers'] ?? 0 }}</span>
            </div>
        </flux:card>
        @if(config('marketplace.features.reviews_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg Rating</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                        @if (($metrics['platform_average_rating'] ?? 0) > 0)
                            {{ number_format((float) $metrics['platform_average_rating'], 2) }} <span class="text-base font-normal text-zinc-500">/ 5.00</span>
                        @else
                            —
                        @endif
                    </span>
                </div>
            </flux:card>
        @endif
        
        {{-- GA Stats Integrated --}}
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Users (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) ? number_format($metrics['ga']['active_users'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sessions (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) ? number_format($metrics['ga']['sessions'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Page Views (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) ? number_format($metrics['ga']['page_views'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bounce Rate (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                    @if (isset($metrics['ga']) && ($metrics['ga']['bounce_rate'] ?? null) !== null)
                        {{ number_format($metrics['ga']['bounce_rate'], 1) }}%
                    @else
                        —
                    @endif
                </span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg. Session (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                    @if (isset($metrics['ga']) && ($metrics['ga']['avg_session_duration'] ?? null) !== null)
                        @php
                            $seconds = (int) $metrics['ga']['avg_session_duration'];
                            $minutes = floor($seconds / 60);
                            $secs = $seconds % 60;
                        @endphp
                        {{ $minutes }}:{{ str_pad((string) $secs, 2, '0', STR_PAD_LEFT) }}
                    @else
                        —
                    @endif
                </span>
            </div>
        </flux:card>
    </div>
HTML;

$content = str_replace($search, $replace, $content);

$search2 = <<<'HTML'
    {{-- Website Traffic --}}
    @if($gaConfigured)
        <flux:card>
            <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Website Traffic (Google Analytics)</flux:heading>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active Users</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ number_format($metrics['ga']['active_users'] ?? 0) }}</span>
                </div>
                <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sessions</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ number_format($metrics['ga']['sessions'] ?? 0) }}</span>
                </div>
                <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Page Views</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ number_format($metrics['ga']['page_views'] ?? 0) }}</span>
                </div>
                <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bounce Rate</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                        @if (($metrics['ga']['bounce_rate'] ?? null) !== null)
                            {{ number_format($metrics['ga']['bounce_rate'], 1) }}%
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg. Session</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                        @if (($metrics['ga']['avg_session_duration'] ?? null) !== null)
                            @php
                                $seconds = (int) $metrics['ga']['avg_session_duration'];
                                $minutes = floor($seconds / 60);
                                $secs = $seconds % 60;
                            @endphp
                            {{ $minutes }}:{{ str_pad((string) $secs, 2, '0', STR_PAD_LEFT) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </flux:card>
    @endif
HTML;

$content = str_replace($search2, '', $content);
file_put_contents('resources/views/livewire/admin/finance/analytics-dashboard.blade.php', $content);
echo 'Done';
