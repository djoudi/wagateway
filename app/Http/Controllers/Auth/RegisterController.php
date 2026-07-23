<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()],
        ]);

        // Every account starts on Starter — paid tiers require manual payment
        // confirmation (see BillingPage). The plan hint from the landing page
        // pricing CTA is preserved so we can prompt an immediate upgrade below.
        $starterPlan = Plan::where('slug', 'starter')->where('is_active', true)->first();

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'plan_id'  => $starterPlan?->id,
        ]);

        $user->generateApiKeys();

        event(new Registered($user));
        Auth::login($user);

        $intendedPlan = $request->query('plan') ?? $request->input('plan');
        if (in_array($intendedPlan, ['pro', 'business'], true)) {
            session(['intended_plan' => $intendedPlan]);
            return redirect()->route('billing');
        }

        return redirect()->route('dashboard');
    }
}
