<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AvailabilitySchedule;
use App\Models\ScheduleConfig;
use App\Models\BlockedSlot;
use App\Models\Reservation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ScheduleGenerator
{
    private const MAX_QUERY_DAYS = 30;
    private const MIN_INTERVAL = 15;
    private const MAX_INTERVAL = 1440;

    public function generate(int $businessId, int $scheduleConfigId): array
    {
        $config = ScheduleConfig::findOrFail($scheduleConfigId);

        $this->validateConfig($config);

        $slots = [];
        $days = $config->days ?? [1, 2]; // Padrão ISO (1-7)
        $startTime = $config->valid_from;
        $endTime = $config->valid_to;
        $interval = $this->normalizeInterval($config->interval ?? 30);

        $period = CarbonPeriod::create(
            now()->startOfWeek(),
            now()->addWeeks(2)->endOfWeek()
        );

        foreach ($period as $date) {
            if (!$this->shouldProcessDay($date, $days)) {
                continue;
            }

            $slots = array_merge($slots, $this->generateDaySlots(
                $businessId,
                $scheduleConfigId,
                $date,
                $startTime,
                $endTime,
                $interval,
                $config
            ));
        }

        return $slots;
    }

    public function recalculateForBusiness(Business $business): void
    {
        try {
            DB::transaction(function () use ($business) {
                AvailabilitySchedule::where('business_id', $business->id)
                    ->where('status', 'available')
                    ->delete();

                foreach ($business->scheduleConfigs as $config) {
                    $this->generate($business->id, $config->id);
                }

                $this->applyManualBlocks($business->id);
                $this->syncWithExistingReservations($business->id);
            });
        } catch (QueryException $e) {
            report($e);
            throw new \RuntimeException('Falha ao recalcular agenda. Tente novamente.');
        }
    }

    public function getAvailability(int $businessId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->diffInDays($end) > self::MAX_QUERY_DAYS) {
            throw new \InvalidArgumentException(
                "Período máximo de consulta é " . self::MAX_QUERY_DAYS . " dias"
            );
        }

        $business = Business::findOrFail($businessId);

        $slots = $this->fetchAvailableSlots($businessId, $start, $end);

        return $this->formatSlotsForResponse($slots, $business->timezone);
    }

    private function generateDaySlots(
        int $businessId,
        int $scheduleConfigId,
        Carbon $date,
        string $startTime,
        string $endTime,
        int $interval,
        ScheduleConfig $config
    ): array {
        $slots = [];
        $current = $date->copy()->setTimeFromTimeString($startTime);
        $end = $date->copy()->setTimeFromTimeString($endTime);

        while ($current <= $end) {
            $slots[] = $this->createSlotRecord(
                $businessId,
                $scheduleConfigId,
                $current,
                $interval,
                $config
            );

            $current->addMinutes($interval);
        }

        return $slots;
    }

    private function createSlotRecord(
        int $businessId,
        int $scheduleConfigId,
        Carbon $start,
        int $interval,
        ScheduleConfig $config
    ): AvailabilitySchedule {
        return AvailabilitySchedule::create([
            'business_id' => $businessId,
            'schedule_config_id' => $scheduleConfigId,
            'valid_from' => $start->copy(),
            'valid_to' => $start->copy()->addMinutes($interval),
            'day' => strtolower($start->englishDayOfWeek),
            'time' => $start->format('H:i'),
            'status' => 'available',
            'config' => $config->toArray(),
            'resource_type' => $config->resource_type ?? null,
            'resource_id' => $config->resource_id ?? null,
        ]);
    }

    private function shouldProcessDay(Carbon $date, array $days): bool
    {
        return in_array($date->dayOfWeekIso, $days);
    }

    private function validateConfig(ScheduleConfig $config): void
    {
        if (Carbon::parse($config->valid_from) >= Carbon::parse($config->valid_to)) {
            throw new \InvalidArgumentException(
                'O horário final deve ser após o horário inicial'
            );
        }
    }

    private function normalizeInterval(int $interval): int
    {
        return max(self::MIN_INTERVAL, min(self::MAX_INTERVAL, $interval));
    }

    private function fetchAvailableSlots(int $businessId, Carbon $start, Carbon $end): Collection
    {
        $cacheKey = $this->buildCacheKey($businessId, $start, $end);

        return Cache::remember($cacheKey, now()->addHour(), function () use ($businessId, $start, $end) {
            return AvailabilitySchedule::query()
                ->where('business_id', $businessId)
                ->whereBetween('valid_from', [$start, $end])
                ->where('status', 'available')
                ->whereNotIn('id', function ($query) {
                    $query->select('availability_schedule_id')
                        ->from('reservations')
                        ->where('status', '!=', 'cancelled');
                })
                ->get();
        });
    }

    private function buildCacheKey(int $businessId, Carbon $start, Carbon $end): string
    {
        return sprintf(
            'business:%d:availability:%s:%s',
            $businessId,
            $start->toDateString(),
            $end->toDateString()
        );
    }

    private function formatSlotsForResponse(Collection $slots, string $timezone): array
    {
        return $slots->map(function ($slot) use ($timezone) {
            return [
                'id' => $slot->id,
                'start' => $slot->valid_from->toIso8601String(),
                'end' => $slot->valid_to->toIso8601String(),
                'start_local' => $slot->valid_from->copy()->setTimezone($timezone)->toDateTimeString(),
                'end_local' => $slot->valid_to->copy()->setTimezone($timezone)->toDateTimeString(),
                'resource' => $slot->resource_id ? [
                    'type' => $slot->resource_type,
                    'id' => $slot->resource_id
                ] : null
            ];
        })->toArray();
    }

    private function applyManualBlocks(int $businessId): void
    {
        BlockedSlot::where('business_id', $businessId)
            ->each(function ($block) use ($businessId) {
                AvailabilitySchedule::where('business_id', $businessId)
                    ->whereBetween('valid_from', [$block->start_time, $block->end_time])
                    ->update(['status' => 'blocked']);
            });
    }

    private function syncWithExistingReservations(int $businessId): void
    {
        $reservedSlotIds = Reservation::where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->pluck('availability_schedule_id');

        if ($reservedSlotIds->isNotEmpty()) {
            AvailabilitySchedule::whereIn('id', $reservedSlotIds)
                ->update(['status' => 'reserved']);
        }
    }
}
