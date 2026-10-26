<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use App\Support\RedirectHelper;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        /** @var User|null $user */
        $user = $request->user();

        $defaultDestination = ($user && $user->isStaff()) ? '/staff' : '/donor';
        $rawReturnTo = (string) session()->pull('return_to');
        $safePath = RedirectHelper::sanitizeRelativePath($rawReturnTo);

        if ($user && $user->isDonor() && $safePath !== null) {
            $destination = $safePath.(str_contains($safePath, '?') ? '&' : '?').'verified=1';
        } else {
            $destination = $defaultDestination.'?verified=1';
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->to($destination);
    }
}
