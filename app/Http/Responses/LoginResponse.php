<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.index');
            }

            if ($user->isCompanyAccount()) {
                return $user->ownedCompany()
                    ? redirect()->route('companies.dashboard', $user->ownedCompany())
                    : redirect()->route('companies.create');
            }

            if (! $user->hasCompletedOnboarding()) {
                return redirect()->route('onboarding');
            }
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(config('fortify.home'));
    }
}
