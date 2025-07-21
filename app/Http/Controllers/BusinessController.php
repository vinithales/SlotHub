<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\GenerateBusinessRequest;
use App\Services\BusinessService;

class BusinessController extends Controller
{
    public function __construct(
        private BusinessService $businessService
    ) {}



    public function generateBusiness(GenerateBusinessRequest $request)
    {
        $data = $request->validated();

        $business =  $this->businessService->generate($data);

        return response()->json([
            'message' => 'Business generated successfully',
            'data' => $business
        ]);

    }
}
