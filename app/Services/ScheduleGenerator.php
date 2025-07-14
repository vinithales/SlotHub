<?php

namespace App\Services;

use App\Models\Business;
use App\Models\AvailabilitySchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\BlockedSlot;
use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ScheduleGenerator
{
    public function generate(int $businessId, array $config): array
    {
        $slots = [];
        $start = Carbon::parse($config['valid_from']);
        $end = Carbon::parse($config['valid_to']);
        $interval = $config['interval'];

        foreach ($config['days'] as $day) {
            $current = $start->copy();

            while ($current <= $end) {
                $slots[] = $this->createSlot(
                    $businessId,
                    $day,
                    $current->format('H:i'),
                    $current->copy(),
                    $current->copy()->addMinutes($interval),
                    $config
                );
                $current->addMinutes($config['interval']);
            }
        }

        return $slots;
    }

    private function createSlot(
        int $businessId,
        string $day,
        string $time,
        Carbon $validFrom,
        Carbon $validTo,
        array $config
    ): array {
        AvailabilitySchedule::create([
            'business_id' => $businessId,
            'day' => $day,
            'time' => $time,
            'status' => 'available',
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'config' => $config,
        ]);

        return compact('day', 'time');
    }

    public function recalculateForBusiness(Business $business): void
    {
        $config = json_decode($business->config_json, true) ?? [];

        AvailabilitySchedule::where('business_id', $business->id)
            ->where('status', 'available')
            ->delete();

        $this->generateSlotsFromConfig($business->id, $config);

        $this->applyManualBlocks($business->id);

        $this->syncWithExistingReservations($business->id);
    }

    private function generateSlotsFromConfig(int $businessId, array $config): void
    {
        $days = $config['days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $startTime = $config['valid_from'] ?? '09:00';
        $endTime = $config['valid_to'] ?? '17:00';
        $interval = $config['interval'] ?? 30;

        $period = CarbonPeriod::create(
            now()->startOfWeek(),
            now()->addWeeks(2)->endOfWeek()
        );

        foreach ($period as $date) {
            if (!in_array(strtolower($date->englishDayOfWeek), $days)) {
                continue;
            }

            $current = $date->copy()->setTimeFromTimeString($startTime);
            $end = $date->copy()->setTimeFromTimeString($endTime);

            while ($current <= $end) {
                AvailabilitySchedule::updateOrCreate([
                    'business_id' => $businessId,
                    'valid_from' => $current,
                    'valid_to' => $current->copy()->addMinutes($interval),
                ], [
                    'status' => 'available',
                    'resource_type' => $config['resource_type'] ?? null,
                    'resource_id' => $config['resource_id'] ?? null,
                    'config' => $config,
                ]);

                $current->addMinutes($interval);
            }
        }
    }
    public function getAvailability(int $businessId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->diffInDays($end) > 30) {
            throw new \InvalidArgumentException('Período máximo de consulta é 30 dias');
        }

        $query = AvailabilitySchedule::where('business_id', $businessId)
            ->where('valid_from', '>=', $start)
            ->where('valid_to', '<=', $end)
            ->whereNotIn('id', function ($subquery) {
                $subquery->select('availability_schedule_id')
                    ->from('reservations')
                    ->where('status', '!=', 'cancelled');
            });

        $cacheKey = "business:{$businessId}:availability:{$start->toDateString()}:{$end->toDateString()}";

        $slots = Cache::remember($cacheKey, now()->addHour(), function () use ($query) {
            return $query->get()->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'start' => optional($slot->valid_from)->toIso8601String(),
                    'end' => optional($slot->valid_to)->toIso8601String(),
                    'resource' => $slot->resource_id ? [
                        'type' => $slot->resource_type,
                        'id' => $slot->resource_id
                    ] : null
                ];
            });
        });


        $business = Business::find($businessId);
        return $this->adjustForTimezone(collect($slots), $business->timezone);
    }

    private function adjustForTimezone(Collection $slots, string $timezone): array
    {
        return $slots->map(function ($slot) use ($timezone) {
            return [
                ...$slot,
                'start_local' => Carbon::parse($slot['start'])->setTimezone($timezone)->format('Y-m-d H:i:s'),
                'end_local' => Carbon::parse($slot['end'])->setTimezone($timezone)->format('Y-m-d H:i:s')
            ];
        })->toArray();
    }

    private function applyManualBlocks(int $businessId): void
    {
        $blocks = BlockedSlot::where('business_id', $businessId)->get();

        foreach ($blocks as $block) {
            AvailabilitySchedule::where('business_id', $businessId)
                ->where('valid_from', '>=', $block->start_time)
                ->where('valid_to', '<=', $block->end_time)
                ->update(['status' => 'blocked']);
        }
    }
    private function syncWithExistingReservations(int $businessId): void
    {
        $reservedSlotIds = Reservation::where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->pluck('availability_schedule_id');

        AvailabilitySchedule::whereIn('id', $reservedSlotIds)
            ->update(['status' => 'reserved']);
    }
}
