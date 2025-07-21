<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ScheduleConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class BusinessService
{
    private function generate(array $data)
    {
        $data['slug'] = \Str::slug($data['name']);

        return DB::transaction(function () use ($data): Business {
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Business::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }

            $business = Business::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'timezone' => $data['timezone'],
            ]);

            foreach ($data['schedule'] as $schedule) {
                foreach ($schedule['days'] as $dayName) {
                    $dayNumber = $this->mapDayNameToNumber($dayName);

                    $business->scheduleConfigs()->create([
                        'day_of_week' => $dayNumber,
                        'valid_from' => $schedule['valid_from'],
                        'valid_to' => $schedule['valid_to'],
                        'interval' => $schedule['interval'],
                    ]);
                }
            }

            return $business;
        });
    }


    private function mapDayNameToNumber(string $day): int
    {
        $map = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $map[strtolower($day)] ?? 0;
    }
}
