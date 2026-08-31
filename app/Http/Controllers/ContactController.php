<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        Enquiry::create($request->validated());

        return response()->json([
            'message' => "Thanks! We'll be in touch soon.",
        ], 201);
    }
}
