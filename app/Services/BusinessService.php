<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ScheduleConfig;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessService
{
    public function generate(array $data)
    {
        $data['slug'] = Str::slug($data['name']);

        return DB::transaction(function () use ($data): Business {
            // Geração de slug único
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Business::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter++;
            }

            // Criação do negócio
            $business = Business::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'timezone' => $data['timezone'],
            ]);

            // Processamento dos horários
            foreach ($data['schedule'] as $schedule) {
                $dayNumbers = [];

                // Converter nomes de dias para números ISO (1-7)
                foreach ($schedule['days'] as $dayName) {
                    $dayNumber = $this->mapDayNameToIsoNumber($dayName);
                    if ($dayNumber !== null) {
                        $dayNumbers[] = $dayNumber;
                    }
                }

                // Validar horários
                if (!$this->validateScheduleTimes($schedule['valid_from'], $schedule['valid_to'])) {
                    continue;
                }

                // Criar configuração de agenda
                $business->scheduleConfigs()->create([
                    'days' => array_unique($dayNumbers), // Remove duplicados
                    'valid_from' => $schedule['valid_from'],
                    'valid_to' => $schedule['valid_to'],
                    'interval' => $this->validateInterval($schedule['interval']),
                    'type' => $schedule['type'] ?? 'default',
                ]);
            }

            return $business;
        });
    }

    private function mapDayNameToIsoNumber(string $day): ?int
    {
        $map = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 7,
        ];

        return $map[strtolower($day)] ?? null;
    }

    private function validateInterval(int $interval): int
    {
        return max(15, min(1440, $interval));
    }


    private function validateScheduleTimes(string $from, string $to): bool
    {
        try {
            $fromTime = Carbon::createFromFormat('H:i', $from);
            $toTime = Carbon::createFromFormat('H:i', $to);

            return $fromTime->lessThan($toTime);
        } catch (\Exception $e) {
            return false;
        }
    }
}
