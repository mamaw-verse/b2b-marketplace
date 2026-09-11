<div
    x-data="dashboardCharts()"
    x-effect="$wire.metrics && $nextTick(() => updateCharts($wire.metrics))"
    class="space-y-6"
>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Platform Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Overview of platform performance and key metrics.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <flux:select wire:model.live="period" size="sm" class="w-40">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="7d">{{ __('Last 7 days') }}</flux:select.option>
                <flux:select.option value="30d">{{ __('Last 30 days') }}</flux:select.option>
                <flux:select.option value="90d">{{ __('Last 90 days') }}</flux:select.option>
                <flux:select.option value="this_month">{{ __('This Month') }}</flux:select.option>
                <flux:select.option value="last_month">{{ __('Last Month') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom Range') }}</flux:select.option>
            </flux:select>

            @if($period === 'custom')
                <flux:input type="date" wire:model.live="customFrom" size="sm" class="w-36" />
                <span class="text-sm text-zinc-500">{{ __('to') }}</span>
                <flux:input type="date" wire:model.live="customTo" size="sm" class="w-36" />
            @endif
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @if(config('marketplace.features.orders_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('GMV') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['gmv'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Orders') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_orders'] ?? 0 }}</span>
                </div>
            </flux:card>
        @endif

        @if(config('marketplace.features.payouts_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Payouts Disbursed') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_payouts_disbursed'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
        @endif

        <a href="{{ route('admin.merchants.index') }}" wire:navigate class="block group">
            <flux:card class="h-full transition group-hover:ring-2 group-hover:ring-amber-400/60">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Businesses') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['total_active_sellers'] ?? 0 }}</span>
                </div>
            </flux:card>
        </a>

        @if(config('marketplace.features.reviews_enabled'))
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Avg Rating') }}</span>
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

        {{-- Open RFQs (pending + in_progress) --}}
        @if(config('marketplace.features.admin_rfq_management_enabled'))
            <a href="{{ route('admin.rfqs.index', ['status' => 'pending']) }}" wire:navigate class="block group">
                <flux:card class="h-full transition group-hover:ring-2 group-hover:ring-amber-400/60">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Open RFQs') }}</span>
                        <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['open_rfq_count'] ?? 0 }}</span>
                        <span class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ __('pending & in progress') }}</span>
                    </div>
                </flux:card>
            </a>
        @endif

        {{-- Open Sourcing Requests (pending + in_progress) --}}
        <a href="{{ route('admin.sourcing-requests.index', ['status' => 'pending']) }}" wire:navigate class="block group">
            <flux:card class="h-full transition group-hover:ring-2 group-hover:ring-amber-400/60">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Open Sourcing') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ $metrics['open_sourcing_count'] ?? 0 }}</span>
                    <span class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ __('pending & in progress') }}</span>
                </div>
            </flux:card>
        </a>

        {{-- Expiring / Expired Certificates --}}
        <a href="{{ route('admin.certifications.index', ['status' => 'expiring_soon']) }}" wire:navigate class="block group">
            <flux:card class="h-full transition {{ ($metrics['expiring_cert_count'] ?? 0) > 0 ? 'group-hover:ring-2 group-hover:ring-red-400/60' : 'group-hover:ring-2 group-hover:ring-amber-400/60' }}">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Expiring Certs') }}</span>
                    <span class="text-2xl font-semibold {{ ($metrics['expiring_cert_count'] ?? 0) > 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }} mt-1 tabular-nums">{{ $metrics['expiring_cert_count'] ?? 0 }}</span>
                    <span class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ __('expiring soon & expired') }}</span>
                </div>
            </flux:card>
        </a>

        {{-- GA Stats --}}
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Users (GA)') }}</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['active_users'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Sessions (GA)') }}</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['sessions'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Page Views (GA)') }}</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">{{ isset($metrics['ga']) && $metrics['ga'] !== null ? number_format($metrics['ga']['page_views'] ?? 0) : '—' }}</span>
            </div>
        </flux:card>
        <flux:card>
            <div class="flex flex-col">
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Bounce Rate (GA)') }}</span>
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
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Avg. Session (GA)') }}</span>
                <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">
                    @if (isset($metrics['ga']) && $metrics['ga'] !== null && ($metrics['ga']['avg_session_duration'] ?? null) !== null)
                        @php
                            $seconds = max(0, (int) $metrics['ga']['avg_session_duration']);
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
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Transaction Fees Collected') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_transaction_fees_collected'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
            <flux:card>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Commission Fees Collected') }}</span>
                    <span class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1 tabular-nums">PHP {{ number_format((float) ($metrics['total_commission_fees_collected'] ?? 0), 2) }}</span>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Revenue & Orders Trend Charts --}}
    @if(config('marketplace.features.orders_enabled'))
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">{{ __('Revenue Trend') }}</flux:heading>
                <div class="relative" style="height: 260px;" wire:ignore>
                    <canvas x-ref="revenueChart"></canvas>
                </div>
            </flux:card>
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">{{ __('Orders Trend') }}</flux:heading>
                <div class="relative" style="height: 260px;" wire:ignore>
                    <canvas x-ref="ordersChart"></canvas>
                </div>
            </flux:card>
        </div>
    @endif

    {{-- Subscriptions & Certifications --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @if(config('marketplace.features.subscriptions_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">{{ __('Subscriptions') }}</flux:heading>
                <div class="flex flex-wrap gap-6 items-start">
                    <div class="shrink-0" style="width: 180px; height: 180px;" wire:ignore>
                        <canvas x-ref="subscriptionsDonut"></canvas>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white mb-2">{{ __('Counts by Status') }}</p>
                        @forelse($metrics['subscriptions']['counts'] ?? [] as $count)
                            <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400 capitalize">{{ $count['status'] ?? '' }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $count['count'] ?? 0 }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subscription data.') }}</p>
                        @endforelse
                        <div class="mt-4 pt-4">
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Trial to Paid Conversion') }}</p>
                            <p class="text-xl font-bold text-zinc-900 dark:text-white tabular-nums mt-1">{{ $metrics['subscriptions']['trial_to_paid_conversion'] ?? 0 }}%</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endif

        @if(config('marketplace.features.certification_apply_enabled'))
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">{{ __('Certifications') }}</flux:heading>
                <div class="flex flex-wrap gap-6 items-start">
                    <div class="shrink-0" style="width: 180px; height: 180px;" wire:ignore>
                        <canvas x-ref="certificationsDonut"></canvas>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        @foreach(['pending', 'approved', 'rejected'] as $status)
                            <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400 capitalize">{{ __(':status Applications', ['status' => $status]) }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $metrics['certifications'][$status] ?? 0 }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        @endif
    </div>

    {{-- Sponsored Content --}}
    @if(config('marketplace.features.sponsored_content_enabled'))
        <div class="grid grid-cols-1 gap-6">
            <flux:card>
                <flux:heading size="sm" class="mb-4 uppercase tracking-wide text-zinc-500">{{ __('Sponsored Content Performance') }}</flux:heading>
                <div class="relative" style="height: 200px;" wire:ignore>
                    <canvas x-ref="sponsoredChart"></canvas>
                </div>
                <div class="mt-6">
                    @forelse($metrics['sponsored_content'] ?? [] as $event)
                        <div class="flex justify-between py-2 border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event['tier_name'] ?? '' }} — <span class="capitalize">{{ $event['event_type'] ?? '' }}</span></span>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $event['count'] ?? 0 }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No event data.') }}</p>
                    @endforelse
                </div>
            </flux:card>
        </div>
    @endif

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
                        animation: { duration: 250 },
                        plugins: {
                            legend: { display: false },
                            tooltip: { mode: 'index', intersect: false },
                        },
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: this.colors.text,
                                    font: { size: 11 },
                                    maxTicksLimit: 10,
                                    autoSkip: true,
                                    maxRotation: 0,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(161, 161, 170, 0.1)' },
                                ticks: { color: this.colors.text, font: { size: 11 } },
                            },
                        },
                    };
                },

                isLiveCanvas(canvas) {
                    return !!(canvas && canvas.isConnected && canvas instanceof HTMLCanvasElement);
                },

                toNumber(value, fallback = 0) {
                    const n = Number(value);
                    return Number.isFinite(n) ? n : fallback;
                },

                normalizeArray(value) {
                    return Array.isArray(value) ? value : [];
                },

                destroyChart(key) {
                    const chart = this.instances[key];
                    if (chart) {
                        try {
                            // Stop pending animations first so no stale
                            // requestAnimationFrame fires on a null canvas.
                            chart.stop();
                            chart.destroy();
                        } catch (e) { /* already gone */ }
                    }
                    this.instances[key] = null;
                },

                /**
                 * Safely initialise a Chart.js instance.
                 *
                 * With responsive:true Chart.js sets up its own
                 * ResizeObserver and sizes itself to the container.
                 * We just need the canvas to be connected to the DOM.
                 */
                safeInit(key, canvas, buildFn) {
                    this.destroyChart(key);

                    if (!this.isLiveCanvas(canvas)) return;

                    try {
                        this.instances[key] = buildFn(canvas);
                    } catch (e) {
                        console.error(`Chart "${key}" init failed`, e);
                    }
                },

                updateCharts(metrics) {
                    if (!metrics || typeof metrics !== 'object') {
                        this.destroyAllCharts();
                        return;
                    }

                    // revenue_trend already contains both gmv and orders per day.
                    const trend = this.normalizeArray(metrics.revenue_trend);

                    this.initRevenueChart(trend);
                    this.initOrdersChart(trend);
                    this.initSubscriptionsDonut(metrics.subscriptions || {});
                    this.initCertificationsDonut(metrics.certifications || {});
                    this.initSponsoredChart(this.normalizeArray(metrics.sponsored_content));
                },

                initRevenueChart(trend) {
                    const canvas = this.$refs.revenueChart;
                    if (!canvas) { this.destroyChart('revenue'); return; }

                    const data = trend.map(d => ({ date: d?.date ?? '', gmv: this.toNumber(d?.gmv) }));

                    this.safeInit('revenue', canvas, (c) => new Chart(c, {
                        type: 'line',
                        data: {
                            labels: data.map(d => d.date),
                            datasets: [{
                                label: 'GMV (PHP)',
                                data: data.map(d => d.gmv),
                                borderColor: this.colors.green,
                                backgroundColor: this.colors.greenLight + '80',
                                fill: true,
                                tension: 0.3,
                                pointRadius: data.length > 30 ? 0 : 3,
                                pointHoverRadius: 5,
                                borderWidth: 2,
                            }],
                        },
                        options: {
                            ...this.defaultOptions(),
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => 'PHP ' + this.toNumber(ctx.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                                    },
                                },
                            },
                        },
                    }));
                },

                initOrdersChart(trend) {
                    const canvas = this.$refs.ordersChart;
                    if (!canvas) { this.destroyChart('orders'); return; }

                    // revenue_trend rows include an 'orders' field — use it directly.
                    const data = trend.map(d => ({ date: d?.date ?? '', orders: this.toNumber(d?.orders) }));

                    this.safeInit('orders', canvas, (c) => new Chart(c, {
                        type: 'bar',
                        data: {
                            labels: data.map(d => d.date),
                            datasets: [{
                                label: 'Orders',
                                data: data.map(d => d.orders),
                                backgroundColor: this.colors.blue,
                                borderRadius: 4,
                                maxBarThickness: 24,
                            }],
                        },
                        options: {
                            ...this.defaultOptions(),
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => 'Orders: ' + this.toNumber(ctx.parsed.y).toLocaleString(),
                                    },
                                },
                            },
                        },
                    }));
                },

                initSubscriptionsDonut(subs) {
                    const canvas = this.$refs.subscriptionsDonut;
                    if (!canvas) { this.destroyChart('subsDonut'); return; }

                    const counts = this.normalizeArray(subs?.counts);
                    const totals = {};
                    counts.forEach(c => {
                        if (!c?.status) return;
                        totals[c.status] = (totals[c.status] || 0) + this.toNumber(c?.count);
                    });

                    const labels = Object.keys(totals);
                    const data = labels.map(l => totals[l]);

                    // Destroy old chart when data disappears — prevents stale donuts.
                    if (data.length === 0) { this.destroyChart('subsDonut'); return; }

                    const bgColors = labels.map(s => s === 'active' ? this.colors.green : s === 'trialing' ? this.colors.blue : this.colors.neutral);

                    this.safeInit('subsDonut', canvas, (c) => new Chart(c, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                            datasets: [{ data, backgroundColor: bgColors, borderWidth: 0 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'bottom', labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, boxWidth: 10, boxHeight: 10 } },
                                tooltip: { callbacks: { label: (ctx) => ctx.label + ': ' + this.toNumber(ctx.raw).toLocaleString() } },
                            },
                        },
                    }));
                },

                initCertificationsDonut(certs) {
                    const canvas = this.$refs.certificationsDonut;
                    if (!canvas) { this.destroyChart('certDonut'); return; }

                    const labels = ['pending', 'approved', 'rejected'];
                    const data = labels.map(s => this.toNumber(certs?.[s]));

                    // Destroy old chart when data disappears.
                    if (data.every(v => v === 0)) { this.destroyChart('certDonut'); return; }

                    const bgColors = [this.colors.amber, this.colors.green, this.colors.red];

                    this.safeInit('certDonut', canvas, (c) => new Chart(c, {
                        type: 'doughnut',
                        data: {
                            labels: labels.map(l => l.charAt(0).toUpperCase() + l.slice(1)),
                            datasets: [{ data, backgroundColor: bgColors, borderWidth: 0 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'bottom', labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, boxWidth: 10, boxHeight: 10 } },
                                tooltip: { callbacks: { label: (ctx) => ctx.label + ': ' + this.toNumber(ctx.raw).toLocaleString() } },
                            },
                        },
                    }));
                },

                initSponsoredChart(events) {
                    const canvas = this.$refs.sponsoredChart;
                    if (!canvas) { this.destroyChart('sponsored'); return; }

                    if (!events.length) { this.destroyChart('sponsored'); return; }

                    const tiers = [...new Set(events.map(e => e?.tier_name).filter(Boolean))];
                    const eventTypes = [...new Set(events.map(e => e?.event_type).filter(Boolean))];

                    if (!tiers.length || !eventTypes.length) { this.destroyChart('sponsored'); return; }

                    const colorPalette = [this.colors.green, this.colors.blue, this.colors.amber, this.colors.red];

                    const datasets = eventTypes.map((type, i) => ({
                        label: type.charAt(0).toUpperCase() + type.slice(1),
                        data: tiers.map(tier => {
                            const match = events.find(e => e?.tier_name === tier && e?.event_type === type);
                            return match ? this.toNumber(match.count) : 0;
                        }),
                        backgroundColor: colorPalette[i % colorPalette.length],
                        borderRadius: 4,
                        maxBarThickness: 20,
                    }));

                    this.safeInit('sponsored', canvas, (c) => new Chart(c, {
                        type: 'bar',
                        data: { labels: tiers, datasets },
                        options: {
                            ...this.defaultOptions(),
                            plugins: {
                                legend: {
                                    display: eventTypes.length > 1,
                                    position: 'bottom',
                                    labels: { color: this.colors.text, font: { size: 11 }, padding: 12, usePointStyle: true, boxWidth: 10, boxHeight: 10 },
                                },
                            },
                            scales: {
                                ...this.defaultOptions().scales,
                                x: { ...this.defaultOptions().scales.x, stacked: true },
                                y: { ...this.defaultOptions().scales.y, stacked: true },
                            },
                        },
                    }));
                },

                destroyAllCharts() {
                    Object.keys(this.instances).forEach(k => this.destroyChart(k));
                },

                // Alpine calls this when the component is torn down (wire:navigate away).
                destroy() {
                    this.destroyAllCharts();
                },
            };
        };
    </script>
</div>