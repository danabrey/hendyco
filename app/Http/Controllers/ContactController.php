<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Mail\NewEnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Save the enquiry and email it to the business inbox. A filled-in `website` field is a
     * honeypot only bots see, so those submissions are thanked but dropped.
     */
    public function store(StoreEnquiryRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return $this->thanks();
        }

        $enquiry = Enquiry::create($request->safe()->except('website'));

        try {
            Mail::to(config('mail.from.address'))->send(new NewEnquiryReceived($enquiry));
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->thanks();
    }

    protected function thanks(): JsonResponse
    {
        return response()->json([
            'message' => "Thanks! We'll be in touch soon.",
        ], 201);
    }
}
