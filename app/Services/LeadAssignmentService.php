<?php

namespace App\Services;

use App\Models\Handler;
use App\Models\Lead;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeadAssignmentService
{
    private const LOCK_KEY = 'lead_assignment.lock';

    /**
     * Tentukan handler (CS) untuk sebuah lead.
     *
     * - Jika $handlerId sudah diberikan (dari Scalev / input manual), dipakai apa adanya.
     * - Jika tidak, auto-assign ke CS aktif dengan strategi dari config leadassignment.
     */
    public function assign(?int $handlerId = null): ?int
    {
        return $this->withAssignmentLock(fn () => $this->assignWithoutLock($handlerId));
    }

    /**
     * Jalankan pembuatan lead di dalam lock yang sama dengan pemilihan handler.
     */
    public function withAssignmentLock(Closure $callback): mixed
    {
        return Cache::lock(self::LOCK_KEY, 10)->block(5, $callback);
    }

    /**
     * Dipakai ketika caller sudah memegang assignment lock.
     */
    public function assignWithoutLock(?int $handlerId = null): ?int
    {
        if ($handlerId) {
            return $handlerId;
        }

        if (!config('leadassignment.auto_assign', true)) {
            return null;
        }

        $activeHandlers = $this->activeHandlerIds();

        if ($activeHandlers->isEmpty()) {
            return null;
        }

        return match (config('leadassignment.strategy', 'least_loaded')) {
            'round_robin' => $this->roundRobin($activeHandlers),
            default => $this->leastLoaded($activeHandlers),
        };
    }

    /**
     * Preview assignment tanpa mengubah database atau cache.
     */
    public function preview(int $count): array
    {
        if ($count < 1 || !config('leadassignment.auto_assign', true)) {
            return [];
        }

        $handlerIds = $this->activeHandlerIds();
        if ($handlerIds->isEmpty()) {
            return [];
        }

        $loads = Lead::query()
            ->whereIn('handler_id', $handlerIds)
            ->where('status_fu', 'new')
            ->groupBy('handler_id')
            ->selectRaw('handler_id, COUNT(*) as load')
            ->pluck('load', 'handler_id')
            ->all();
        $assignments = [];
        $roundRobinIndex = (int) Cache::get('lead_assignment.rr_index', -1);

        for ($i = 0; $i < $count; $i++) {
            if (config('leadassignment.strategy', 'least_loaded') === 'round_robin') {
                $roundRobinIndex = ($roundRobinIndex + 1) % $handlerIds->count();
                $handlerId = $handlerIds[$roundRobinIndex];
            } else {
                $handlerId = $handlerIds->sortBy(fn ($id) => [$loads[$id] ?? 0, $id])->first();
            }

            $assignments[] = $handlerId;
            $loads[$handlerId] = ($loads[$handlerId] ?? 0) + 1;
        }

        return $assignments;
    }

    private function activeHandlerIds(): Collection
    {
        return Handler::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->pluck('id');
    }

    /**
     * Assign ke CS dengan beban kerja paling ringan (paling sedikit lead belum di-follow-up).
     * Tie-break deterministik: CS dengan id terkecil.
     */
    private function leastLoaded(Collection $handlerIds): int
    {
        $loads = Lead::query()
            ->whereIn('handler_id', $handlerIds)
            ->where('status_fu', 'new')
            ->groupBy('handler_id')
            ->selectRaw('handler_id, COUNT(*) as load')
            ->pluck('load', 'handler_id')
            ->all();

        $minLoad = null;
        $candidates = [];

        foreach ($handlerIds as $id) {
            $load = $loads[$id] ?? 0;
            if ($minLoad === null || $load < $minLoad) {
                $minLoad = $load;
                $candidates = [$id];
            } elseif ($load === $minLoad) {
                $candidates[] = $id;
            }
        }

        return $candidates[0];
    }

    /**
     * Assign bergantian (rotasi) antar CS aktif, state disimpan di cache.
     */
    private function roundRobin(Collection $handlerIds): int
    {
        $ids = $handlerIds->values()->all();
        $count = count($ids);

        $key = 'lead_assignment.rr_index';
        $next = ((int) Cache::get($key, -1) + 1) % $count;
        Cache::forever($key, $next);

        return $ids[$next];
    }
}
