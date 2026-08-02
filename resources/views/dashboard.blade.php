<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Dashboard CRM</h2>
                <p class="text-sm text-slate-500 mt-0.5">Analisis performa bisnis dan konversi Anda</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Filters --}}
            <div x-data="{ showFilters: false }" class="bg-white rounded-xl shadow-sm border border-slate-200/60 mb-6">
                <button x-on:click="showFilters = !showFilters"
                    class="w-full flex justify-between items-center p-4 sm:hidden text-sm font-medium text-slate-700">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Filter Tanggal
                    </span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="showFilters ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="p-4 sm:p-5" :class="showFilters ? 'block' : 'hidden sm:block'">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Dari Tanggal</label>
                            <input type="date" name="start_date" value="{{ $startDate }}"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Sampai Tanggal</label>
                            <input type="date" name="end_date" value="{{ $endDate }}"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 shadow-sm shadow-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-slate-500 to-slate-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Total Leads</p>
                            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ number_format($stats['total'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Follow-up</p>
                            <p class="text-xl font-bold text-blue-600 mt-0.5">{{ number_format($stats['followed_up'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Belum FU</p>
                            <p class="text-xl font-bold text-amber-600 mt-0.5">{{ number_format($stats['not_followed_up'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Closing</p>
                            <p class="text-xl font-bold text-emerald-600 mt-0.5">{{ number_format($stats['closing'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Revenue</p>
                            <p class="text-lg font-bold text-purple-600 mt-0.5 truncate">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Konversi</p>
                            <p class="text-xl font-bold text-indigo-600 mt-0.5">{{ $stats['conversion_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-4 hover:shadow-md transition-all duration-200 hover:-translate-y-0.5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-500 truncate">Rata-rata Response</p>
                            <p class="text-xl font-bold text-teal-600 mt-0.5">
                                @if($stats['avg_response_time_minutes'])
                                    {{ $stats['avg_response_time_minutes'] }} <span class="text-xs font-normal">mnt</span>
                                @else
                                    <span class="text-base font-normal text-slate-400">—</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Premium Charts Layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Chart 1: Daily Trend --}}
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/60 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">Tren Leads & Closing Harian</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Statistik pertumbuhan leads dan transaksi sukses</p>
                        </div>
                    </div>
                    <div class="h-80 relative">
                        <canvas id="dailyTrendChart"></canvas>
                    </div>
                </div>

                {{-- Chart 2: Funnel Stage --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-slate-900">Distribusi Funnel Stage</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Proporsi leads berdasarkan kedekatan dengan pembelian</p>
                    </div>
                    <div class="h-80 relative flex items-center justify-center">
                        <canvas id="funnelChart"></canvas>
                    </div>
                </div>

                {{-- Chart 3: Status FU Distribution --}}
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200/60 p-5">
                    <div>
                        <h3 class="font-semibold text-slate-900">Distribusi Status Follow-up</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Breakdown detail status follow-up leads saat ini</p>
                    </div>
                    <div class="h-80 relative mt-4">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                {{-- Chart 4: Traffic Type Distribution --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-slate-900">Tipe Trafik (Traffic Type)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Kategorisasi asal usul leads berdasarkan media</p>
                    </div>
                    <div class="h-80 relative flex items-center justify-center">
                        <canvas id="trafficChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Per-CS Performance --}}
            @if($handlerStats->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-slate-200/60 mb-6 overflow-hidden">
                    <div class="p-5 border-b border-slate-200/60 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900">Performa Per CS</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Rekap leads masuk, follow-up, closing, dan revenue tiap CS pada periode terpilih</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-5 py-3">CS</th>
                                    <th class="px-5 py-3 text-right">Total Leads</th>
                                    <th class="px-5 py-3 text-right">Follow-up</th>
                                    <th class="px-5 py-3 text-right">Belum FU</th>
                                    <th class="px-5 py-3 text-right">Closing</th>
                                    <th class="px-5 py-3 text-right">Revenue</th>
                                    <th class="px-5 py-3 text-right">Konversi</th>
                                    <th class="px-5 py-3 text-right">Avg Respon</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($handlerStats as $h)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">{{ $h['initial'] }}</div>
                                                <span class="font-semibold text-slate-800">{{ $h['name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-slate-900">{{ number_format($h['total'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-3.5 text-right text-blue-600 font-medium">{{ number_format($h['followed_up'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-3.5 text-right text-amber-600 font-medium">{{ number_format($h['not_followed_up'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-3.5 text-right text-emerald-600 font-semibold">{{ number_format($h['closing'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-3.5 text-right font-medium text-purple-600">Rp {{ number_format($h['revenue'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ $h['conversion_rate'] }}%</td>
                                        <td class="px-5 py-3.5 text-right text-slate-600">
                                            @if($h['avg_response_time_minutes'] !== null)
                                                {{ number_format($h['avg_response_time_minutes'], 0, ',', '.') }} mnt
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Chart 1: Daily Trend
            const dailyData = @json($dailyData);
            const dailyLabels = dailyData.map(d => {
                if (!d.date_val) return '-';
                const date = new Date(d.date_val);
                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            });
            const totalLeads = dailyData.map(d => d.total_leads);
            const closingLeads = dailyData.map(d => d.total_closing);

            const ctxDaily = document.getElementById('dailyTrendChart').getContext('2d');
            new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [
                        {
                            label: 'Total Leads',
                            data: totalLeads,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            borderWidth: 2.5,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#6366f1',
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Closing',
                            data: closingLeads,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            borderWidth: 2.5,
                            tension: 0.35,
                            fill: true,
                            pointBackgroundColor: '#10b981',
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    family: 'Figtree',
                                    size: 12,
                                    weight: '500'
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                stepSize: 1,
                                font: {
                                    family: 'Figtree',
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Figtree',
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Chart 2: Funnel Stage
            const funnelData = @json($funnelData);
            const funnelLabels = funnelData.map(d => d.funnel_stage ? d.funnel_stage.charAt(0).toUpperCase() + d.funnel_stage.slice(1) : 'Unknown');
            const funnelCounts = funnelData.map(d => d.count);

            const ctxFunnel = document.getElementById('funnelChart').getContext('2d');
            new Chart(ctxFunnel, {
                type: 'doughnut',
                data: {
                    labels: funnelLabels,
                    datasets: [{
                        data: funnelCounts,
                        backgroundColor: ['#f43f5e', '#fbbf24', '#38bdf8', '#94a3b8'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    family: 'Figtree',
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });

            // Chart 3: Status FU
            const statusData = @json($statusData);
            const statusLabels = statusData.map(d => {
                if (!d.status_fu) return 'Unknown';
                return d.status_fu.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
            });
            const statusCounts = statusData.map(d => d.count);

            const ctxStatus = document.getElementById('statusChart').getContext('2d');
            new Chart(ctxStatus, {
                type: 'bar',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusCounts,
                        backgroundColor: '#6366f1',
                        hoverBackgroundColor: '#4f46e5',
                        borderRadius: 8,
                        borderWidth: 0,
                        barThickness: 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                stepSize: 1,
                                font: {
                                    family: 'Figtree',
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Figtree',
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Chart 4: Traffic Type
            const trafficData = @json($trafficData);
            const trafficLabels = trafficData.map(d => d.traffic_type ? d.traffic_type.charAt(0).toUpperCase() + d.traffic_type.slice(1) : 'Direct/Unknown');
            const trafficCounts = trafficData.map(d => d.count);

            const ctxTraffic = document.getElementById('trafficChart').getContext('2d');
            new Chart(ctxTraffic, {
                type: 'polarArea',
                data: {
                    labels: trafficLabels,
                    datasets: [{
                        data: trafficCounts,
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.75)',
                            'rgba(139, 92, 246, 0.75)',
                            'rgba(100, 116, 139, 0.75)',
                            'rgba(236, 72, 153, 0.75)'
                        ],
                        borderWidth: 1.5,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    family: 'Figtree',
                                    size: 11,
                                    weight: '500'
                                }
                            }
                        }
                    },
                    scales: {
                        r: {
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
