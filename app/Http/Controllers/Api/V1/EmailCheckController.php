<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Verifier\EmailVerifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailCheckController extends Controller
{
    public function check(Request $request, EmailVerifierService $verifier): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:320'],
        ]);

        $result = $verifier->verify($data['email']);

        return response()->json([
            'data' => $result->toArray(),
            'disclaimer' => 'Email verification results are not guaranteed to be 100% accurate.',
        ]);
    }
}
