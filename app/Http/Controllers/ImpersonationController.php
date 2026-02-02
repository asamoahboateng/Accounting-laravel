<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    public function impersonate(User $user)
    {
        $currentUser = Auth::user();

        // Check if current user can impersonate
        if (!$currentUser || !$currentUser->canImpersonate()) {
            abort(403, 'You are not authorized to impersonate users.');
        }

        // Check if target user can be impersonated
        if (!$user->canBeImpersonated()) {
            abort(403, 'This user cannot be impersonated.');
        }

        // Store the impersonator's ID
        Session::put('impersonator_id', $currentUser->id);
        Session::put('impersonating', true);

        // Store the target user's password hash for AuthenticateSession middleware
        // This prevents the middleware from logging out the user due to password hash mismatch
        Session::put('password_hash_web', $user->getAuthPassword());

        // Update the session auth key to the new user's ID
        Session::put(Auth::guard('web')->getName(), $user->id);

        // Explicitly save the session before redirect
        Session::save();

        // Redirect to the user's first company or admin home
        $company = $user->companies()->first();

        if ($company) {
            return redirect('/admin/' . $company->id);
        }

        return redirect('/admin');
    }

    public function stopImpersonating()
    {
        $impersonatorId = Session::pull('impersonator_id');
        Session::forget('impersonating');

        if ($impersonatorId) {
            $impersonator = User::find($impersonatorId);

            if ($impersonator) {
                // Update session password hash for the original user
                Session::put('password_hash_web', $impersonator->getAuthPassword());

                // Update the session auth key back to the impersonator's ID
                Session::put(Auth::guard('web')->getName(), $impersonator->id);

                // Explicitly save the session before redirect
                Session::save();

                $company = $impersonator->companies()->first();

                if ($company) {
                    return redirect('/admin/' . $company->id);
                }
            }
        }

        return redirect('/admin');
    }
}
