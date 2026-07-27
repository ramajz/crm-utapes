<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    {{-- Desktop Top Navigation (hidden on mobile) --}}
    <nav class="bg-gradient-to-r from-indigo-950 via-indigo-900 to-slate-900 border-b border-indigo-800/50 hidden md:block shadow-lg shadow-indigo-900/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-indigo-500/30">
                                U
                            </div>
                            <span class="text-lg font-bold text-white tracking-tight">Utapes <span class="font-light text-indigo-300">CRM</span></span>
                        </a>
                    </div>
                    <div class="hidden sm:flex sm:items-center sm:ms-10 space-x-1">
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate
                            class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                            {{ request()->routeIs('dashboard')
                                ? 'bg-white/15 text-white shadow-sm'
                                : 'text-indigo-200 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('leads.index')" :active="request()->routeIs('leads.*')" wire:navigate
                            class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                            {{ request()->routeIs('leads.*')
                                ? 'bg-white/15 text-white shadow-sm'
                                : 'text-indigo-200 hover:text-white hover:bg-white/10' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Leads
                        </x-nav-link>
                    </div>
                </div>
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-indigo-200 hover:text-white hover:bg-white/10 transition-all duration-200">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="max-w-[120px] truncate"></div>
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                {{ __('Profile') }}
                            </x-dropdown-link>
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link class="flex items-center gap-2 text-red-600 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </nav>

    {{-- Mobile Bottom Navigation (hidden on desktop) --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-lg border-t border-slate-200 z-50 md:hidden shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
        <div class="flex justify-around items-center h-16 px-2">
            @php
                $isDashboard = request()->routeIs('dashboard');
                $isLeads = request()->routeIs('leads.*');
                $isProfile = request()->routeIs('profile');
            @endphp

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" wire:navigate
                class="flex flex-col items-center justify-center px-3 py-1.5 rounded-xl text-xs transition-all duration-200 relative
                {{ $isDashboard ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                @if($isDashboard)
                <span class="absolute -top-0.5 w-6 h-1 rounded-full bg-indigo-600"></span>
                @endif
                <svg class="w-6 h-6 mb-0.5" fill="{{ $isDashboard ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="font-medium {{ $isDashboard ? 'text-indigo-600' : '' }}">Dashboard</span>
            </a>

            {{-- Leads --}}
            <a href="{{ route('leads.index') }}" wire:navigate
                class="flex flex-col items-center justify-center px-3 py-1.5 rounded-xl text-xs transition-all duration-200 relative
                {{ $isLeads ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                @if($isLeads)
                <span class="absolute -top-0.5 w-6 h-1 rounded-full bg-indigo-600"></span>
                @endif
                <svg class="w-6 h-6 mb-0.5" fill="{{ $isLeads ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="font-medium {{ $isLeads ? 'text-indigo-600' : '' }}">Leads</span>
            </a>

            {{-- Profile --}}
            <a href="{{ route('profile') }}" wire:navigate
                class="flex flex-col items-center justify-center px-3 py-1.5 rounded-xl text-xs transition-all duration-200 relative
                {{ $isProfile ? 'text-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">
                @if($isProfile)
                <span class="absolute -top-0.5 w-6 h-1 rounded-full bg-indigo-600"></span>
                @endif
                <svg class="w-6 h-6 mb-0.5" fill="{{ $isProfile ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="font-medium {{ $isProfile ? 'text-indigo-600' : '' }}">Profile</span>
            </a>

            {{-- Logout --}}
            <button wire:click="logout"
                class="flex flex-col items-center justify-center px-3 py-1.5 rounded-xl text-xs text-slate-400 hover:text-red-500 transition-all duration-200">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </button>
        </div>
    </nav>

    {{-- Mobile header (shows on mobile only) --}}
    <div class="bg-white/90 backdrop-blur-sm border-b border-slate-200/60 md:hidden">
        <div class="flex items-center justify-between h-14 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-bold text-slate-800">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                    U
                </div>
                <span class="text-base">Utapes CRM</span>
            </a>
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-slate-500">{{ auth()->user()->name }}</span>
                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </div>
    </div>
</div>
