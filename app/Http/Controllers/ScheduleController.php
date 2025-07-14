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
        $validated = $request->validated();

        AvailabilitySchedule::where('business_id', $validated['business_id'])->delete();

        $schedule = $this->scheduleGenerator->generate(
            $validated['business_id'],
            $validated['config']
        );

        return response()->json([
            'message' => 'Schedule generated successfully',
            'data' => $schedule
        ]);
    }


    public function updateConfig(UpdateConfigRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $business = Business::findOrFail($validated['business_id']);
        $business->update([
            'config' => $validated['config']
        ]);


        $this->scheduleGenerator->recalculateForBusiness($business);

        return response()->json([
            'message' => 'Configuration updated successfully'
        ]);
    }


    public function listAvailability(ListAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $slots = $this->scheduleGenerator->getAvailability(
            $validated['business_id'],
            $validated['valid_from'],
            $validated['valid_to']
        );

        return response()->json([
            'data' => $slots
        ]);
    }
}
