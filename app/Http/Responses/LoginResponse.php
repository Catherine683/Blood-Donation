<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Models\User;
use App\Support\RedirectHelper;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        /** @var User|null $user */
        $user = $request->user();

        $defaultDestination = ($user && $user->isStaff()) ? '/staff' : '/donor';

        $rawReturnTo = (string) ($request->input('return_to') ?: session()->pull('url.intended') ?: session()->get('return_to'));
        $safePath = RedirectHelper::sanitizeRelativePath($rawReturnTo);

        if ($user && $user->isDonor() && $safePath !== null) {
            $destination = $user->hasVerifiedEmail() ? $safePath : $defaultDestination;
        } else {
            $destination = $defaultDestination;
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->to($destination);
    }
}
