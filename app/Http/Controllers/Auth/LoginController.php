<?php

namespace App\Http\Controllers\Auth;

use App\Actions\SetPortalUserLanguage;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticationRequest;
use App\SystemSetting;
use App\Traits\Throttles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SeanKndy\SonarApi\Client;

class LoginController extends Controller
{
    use Throttles;

    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }

    public function show(): View
    {
        return view('pages.root.login', [
            'systemSetting' => SystemSetting::instance()
        ]);
    }

    public function authenticate(
        AuthenticationRequest $request,
        SetPortalUserLanguage $setPortalUserLanguage
    ): RedirectResponse {
        if ($this->getThrottleValue("login", $this->generateLoginThrottleHash($request)) > 10) {
            return redirect()->back()->withErrors(utrans("errors.tooManyFailedAuthenticationAttempts",[],$request));
        }

        if (Auth::attempt($request->only('username', 'password'))) {
            Log::info("user " . $request->input('username') . " logged in from " . $request->ip());

            $request->session()->put(
                'account',
                $this->sonarClient
                    ->accounts()
                    ->with([
                        'addresses',
                        'accountStatus',
                        'childAccounts' => fn($query) => $query->with('addresses'),
                    ])
                    ->where('id', Auth::user()->accountId)
                    ->first()
            );

            $this->resetThrottleValue("login", $this->generateLoginThrottleHash($request));

            $setPortalUserLanguage(Auth::user(), $request->input('language'));

            return redirect()->action("BillingController@index");
        } else {
            Log::info("auth attempt for user " . $request->input('username') . " failed from " . $request->ip());
        }

        $this->incrementThrottleValue("login", $this->generateLoginThrottleHash($request));

        return redirect()->back()->withErrors(utrans("errors.couldNotFindAccount",[],$request));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('login');
    }

    private function generateLoginThrottleHash(AuthenticationRequest $request): string
    {
        return \md5($request->input('username') . "_" . $request->getClientIp());
    }
}