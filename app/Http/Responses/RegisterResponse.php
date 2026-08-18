<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => true]);
        }

        $user = auth()->user();

        if ($user->isCompanyAccount()) {
            return $user->ownedCompany()
                ? redirect()->route('companies.onboarding', $user->ownedCompany())
                : redirect()->route('companies.create');
        }

        return redirect()->route('onboarding');
    }
}
