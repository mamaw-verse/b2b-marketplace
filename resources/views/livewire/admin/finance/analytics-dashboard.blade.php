<div x-data="dashboardCharts()" x-effect="$wire.metrics && $nextTick(() => updateCharts($wire.metrics))" class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Platform Dashboard</flux:heading>
            <flux:subheading>Overview of platform performance and key metrics.</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="period" size="sm" class="w-40">
                <flux:select.option value="today">Today</flux:select.option>
                <flux:select.option value="7d">Last 7 days</flux:select.option>
                <flux:select.option value="30d">Last 30 days</flux:select.option>
                <flux:select.option value="90d">Last 90 days</flux:select.option>
                <flux:select.option value="this_month">This Month</flux:select.option>
                <flux:select.option value="last_month">Last Month</flux:select.option>
                <flux:select.option value="custom">Custom Range</flux:select.option>
            </flux:select>

            @if($period === 'custom')
                <flux:input type="date" wire:model.live="customFrom" size="sm" class="w-36" />
                <span class="text-sm text-zinc-500">to</span>
                <flux:input type="date" wire:model.live="customTo" size="sm" class="w-36" />
            @endif
        </div>
    </div>

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
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['active_users'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Sessions (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['sessions'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Page Views (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['page_views'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Bounce Rate (GA)</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                    @if (isset($metrics['ga']) && $metrics['ga'] !== null && ($metrics['ga']['bounce_rate'] ?? null) !== null)
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
                    @if (isset($metrics['ga']) && $metrics['ga'] !== null && ($metrics['ga']['avg_session_duration'] ?? null) !== null)
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

    {{-- Fees Row --}}
    @if(config('marketplace.features.payouts_enabled'))
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Transaction Fees Collected</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_transaction_fees_collected'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Commission Fees Collected</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_commission_fees_collected'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Revenue & Orders Trend Charts --}}
    @if(config('marketplace.features.orders_enabled'))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Revenue Trend</flux:heading>
                <div class="relative" style="height: 260px;">
                    <canvas x-ref="revenueChart"></canvas>
                </div>
            </flux:card>
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Orders Trend</flux:heading>
                <div class="relative" style="height: 260px;">
                    <canvas x-ref="ordersChart"></canvas>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Subscriptions & Certifications --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if(config('marketplace.features.subscriptions_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Subscriptions</flux:heading>
                <div class="flex flex-wrap gap-6 items-start">
                    <div class="relative" style="width: 180px; height: 180px;">
                        <canvas x-ref="subscriptionsDonut"></canvas>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">Counts by Plan</p>
                        @forelse($metrics['subscriptions']['counts'] ?? [] as $count)
                            <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $count->plan_name }} — <span class="capitalize">{{ $count->status }}</span></span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $count->count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No subscription data.</p>
                        @endforelse
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">Trial to Paid Conversion</p>
                            <p class="text-xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $metrics['subscriptions']['trial_to_paid_conversion'] ?? 0 }}%</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        @if(config('marketplace.features.certification_apply_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Certifications</flux:heading>
                <div class="flex flex-wrap gap-6 items-start">
                    <div class="relative" style="width: 180px; height: 180px;">
                        <canvas x-ref="certificationsDonut"></canvas>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        @foreach(['pending', 'approved', 'rejected'] as $status)
                            <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400 capitalize">{{ $status }} Applications</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $metrics['certifications'][$status] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        @endif
    </div>

    {{-- RFQs & Sponsored Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if(config('marketplace.features.admin_rfq_management_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">RFQs</flux:heading>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total RFQs Created</span>
                        <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['rfqs']['volume'] ?? 0 }}</span>
                    </div>
                    <div class="flex flex-col p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/50">
                        @php
                            $rfqSeconds = $metrics['rfqs']['avg_resolution_time_seconds'] ?? 0;
                            $rfqHours = floor($rfqSeconds / 3600);
                        @endphp
                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg Resolution Time</span>
                        <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $rfqHours }} hrs</span>
                    </div>
                </div>
            </flux:card>
        @endif

        @if(config('marketplace.features.sponsored_content_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">Sponsored Content Performance</flux:heading>
                <div class="relative" style="height: 200px;">
                    <canvas x-ref="sponsoredChart"></canvas>
                </div>
                <div class="mt-6">
                    @forelse($metrics['sponsored_content'] ?? [] as $event)
                        <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->tier_name }} — <span class="capitalize">{{ $event->event_type }}</span></span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $event->count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No event data.</p>
                    @endforelse
                </div>
            </flux:card>
        @endif
    </div>

    {{-- Chart.js + Alpine dashboard component --}}
    <script>
        window.dashboardCharts = function () {
            return {
                instances: {},

                colors: {
                    green: '#10b981',
                    greenLight: '#d1fae5',
                    blue: '#3b82f6',
                    blueLight: '#dbeafe',
                    amber: '#f59e0b',
                    amberLight: '#fef3c7',
                    red: '#ef4444',
                    redLight: '#fee2e2',
                    neutral: '#d1d5db',
                    text: '#71717a',
                },

                defaultOptions() {
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: this.colors.text, font: { size: 11 }, maxTicksLimit: 10 },
                            },
                            y: {
                                grid: { color: 'rgba(161, 161, 170, 0.1)' },
                                ticks: { color: this.colors.text, font: { size: 11 } },
                            },
                        },
                    };
                },

                updateCharts(metrics) {
                    this.initRevenueChart(metrics.revenue_trend || []);
                    this.initOrdersChart(metrics.revenue_trend || []);
                    this.initSubscriptionsDonut(metrics.subscriptions || {});
                    this.initCertificationsDonut(metrics.certifications || {});
                    this.initSponsoredChart(metrics.sponsored_content || []);
                },

                destroyChart(key) {
                    if (this.instances[key]) {
                        this.instances[key].destroy();
                        this.instances[key] = null;
                    }
                },

                initRevenueChart(trend) {
                    const canvas = this.$refs.revenueChart;
                    if (!canvas) return;
                    this.destroyChart('revenue');

                    this.instances.revenue = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: trend.map(d => d.date),
                            datasets: [{
                                label: 'GMV (PHP)',
                                data: trend.map(d => d.gmv),
                                borderColor: this.colors.green,
                                backgroundColor: this.colors.greenLight + '80',
                                fill: true,
                                tension: 0.3,
                                pointRadius: trend.length > 30 ? 0 : 3,
                                pointHoverRadius: 5,
                            }],
                        },
                        options: {
                            ...this.defaultOptions(),
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => 'PHP ' + ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2 }),
                                    },
                                },
                            },
                        },
                    });
                },

                initOrdersChart(trend) {
                    const canvas = this.$refs.ordersChart;
                    if (!canvas) return;
                    this.destroyChart('orders');

                    this.instances.orders = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: trend.map(d => d.date),
                            datasets: [{
                                label: 'Orders',
                                data: trend.map(d => d.orders),
                                backgroundColor: this.colors.blue,
                                borderRadius: 4,
                                maxBarThickness: 24,
                            }],
                        },
                        options: this.defaultOptions(),
                    });
                },

                initSubscriptionsDonut(subs) {
                    const canvas = this.$refs.subscriptionsDonut;
                    if (!canvas) return;
                    this.destroyChart('subsDonut');

                    const counts = subs.counts || [];
                    const statusTotals = {};
                    counts.forEach(c => {
                        statusTotals[c.status] = (statusTotals[c.status] || 0) + c.count;
                    });

                    const labels = Object.keys(statusTotals);
                    const data = Object.values(statusTotals);
                    const bgColors = labels.map(s => {
                        if (s === 'active') return this.colors.green;
                        if (s === 'trialing') return this.colors.blue;
                        return this.colors.neutral;
                    });

                    if (data.length === 0) return;

                    this.instances.subsDonut = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                            datasets: [{ data, backgroundColor: bgColors, borderWidth: 0 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, pointStyleWidth: 8 },
                                },
                            },
                        },
                    });
                },

                initCertificationsDonut(certs) {
                    const canvas = this.$refs.certificationsDonut;
                    if (!canvas) return;
                    this.destroyChart('certDonut');

                    const labels = ['pending', 'approved', 'rejected'];
                    const data = labels.map(s => certs[s] || 0);
                    const bgColors = [this.colors.amber, this.colors.green, this.colors.red];

                    if (data.every(v => v === 0)) return;

                    this.instances.certDonut = new Chart(canvas, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                            datasets: [{ data, backgroundColor: bgColors, borderWidth: 0 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, pointStyleWidth: 8 },
                                },
                            },
                        },
                    });
                },

                initSponsoredChart(events) {
                    const canvas = this.$refs.sponsoredChart;
                    if (!canvas) return;
                    this.destroyChart('sponsored');

                    if (!events.length) return;

                    const tiers = [...new Set(events.map(e => e.tier_name))];
                    const eventTypes = [...new Set(events.map(e => e.event_type))];

                    const colorPalette = [this.colors.green, this.colors.blue, this.colors.amber, this.colors.red];

                    const datasets = eventTypes.map((type, i) => ({
                        label: type.charAt(0).toUpperCase() + type.slice(1),
                        data: tiers.map(tier => {
                            const match = events.find(e => e.tier_name === tier && e.event_type === type);
                            return match ? match.count : 0;
                        }),
                        backgroundColor: colorPalette[i % colorPalette.length],
                        borderRadius: 4,
                        maxBarThickness: 20,
                    }));

                    this.instances.sponsored = new Chart(canvas, {
                        type: 'bar',
                        data: { labels: tiers, datasets },
                        options: {
                            ...this.defaultOptions(),
                            plugins: {
                                legend: {
                                    display: eventTypes.length > 1,
                                    position: 'bottom',
                                    labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, pointStyleWidth: 8 },
                                },
                            },
                            scales: {
                                ...this.defaultOptions().scales,
                                x: { ...this.defaultOptions().scales.x, stacked: true },
                                y: { ...this.defaultOptions().scales.y, stacked: true },
                            },
                        },
                    });
                },

                init() {
                    this.$nextTick(() => this.updateCharts(this.$wire.metrics));
                },
            };
        };
    </script>
</div>
