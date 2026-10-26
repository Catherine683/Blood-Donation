<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class PasskeyEndpointController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'enroll' => route('security.edit'),
            'manage' => route('security.edit'),
        ]);
    }
}
