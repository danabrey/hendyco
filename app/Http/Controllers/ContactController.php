<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Mail\NewEnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        $enquiry = Enquiry::create($request->validated());

        try {
            Mail::to(config('mail.from.address'))->send(new NewEnquiryReceived($enquiry));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'message' => "Thanks! We'll be in touch soon.",
        ], 201);
    }
}
