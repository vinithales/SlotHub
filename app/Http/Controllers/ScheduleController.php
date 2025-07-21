<?php

namespace App\Http\Controllers;

use App\Models\AvailabilitySchedule;
use App\Models\Business;
use App\Http\Requests\GenerateScheduleRequest;
use App\Http\Requests\UpdateConfigRequest;
use App\Http\Requests\ListAvailabilityRequest;
use App\Services\ScheduleGenerator;
use Illuminate\Http\JsonResponse;



class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleGenerator $scheduleGenerator
    ) {}


    public function generateSchedule(GenerateScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        AvailabilitySchedule::where('business_id', $data['business_id'])->delete();

        $schedule = $this->scheduleGenerator->generate(
            $data['business_id'],
            $data['config']
        );

        return response()->json([
            'message' => 'Schedule generated successfully',
            'data' => $schedule
        ]);
    }


    public function updateConfig(UpdateConfigRequest $request): JsonResponse
    {
        $data = $request->validated();

        $business = Business::findOrFail($data['business_id']);
        $business->update([
            'config' => $data['config']
        ]);


        $this->scheduleGenerator->recalculateForBusiness($business);

        return response()->json([
            'message' => 'Configuration updated successfully'
        ]);
    }


    public function listAvailability(ListAvailabilityRequest $request): JsonResponse
    {
        $data = $request->validated();

        $slots = $this->scheduleGenerator->getAvailability(
            $data['business_id'],
            $data['valid_from'],
            $data['valid_to']
        );

        return response()->json([
            'data' => $slots
        ]);
    }
}
