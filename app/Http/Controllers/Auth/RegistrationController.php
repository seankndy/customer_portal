<?php

namespace App\Http\Controllers\Auth;

use App\CreationToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountCreationRequest;
use App\Http\Requests\MakeRegistrationTokenRequest;
use App\Traits\Throttles;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use SonarSoftware\CustomerPortalFramework\Controllers\AccountAuthenticationController;

class RegistrationController extends Controller
{
    use Throttles;

    private AccountAuthenticationController $accountAuthenticationController;

    public function __construct(AccountAuthenticationController $accountAuthenticationController)
    {
        $this->accountAuthenticationController = $accountAuthenticationController;
    }

    public function show(): View
    {
        return view('pages.root.register');
    }

    public function makeRegistrationToken(MakeRegistrationTokenRequest $request): RedirectResponse
    {
        if ($this->getThrottleValue("email_lookup", md5($request->getClientIp())) > 10) {
            return redirect()->back()->withErrors(utrans("errors.tooManyFailedLookupAttempts",[],$request));
        }

        try {
            $result = $this->accountAuthenticationController->lookupEmail($request->input('email'));
        } catch (\Exception $e) {
            $this->incrementThrottleValue("email_lookup", md5($request->getClientIp()));

            Log::info("Failed to make registration token for " . $request->email . ": " . $e->getMessage());

            return redirect()->back()->withErrors(utrans("errors.emailLookupFailed",[],$request));
        }

        $creationToken = CreationToken::where('account_id', '=', $result->account_id)
            ->where('contact_id', '=', $result->contact_id)
            ->first();

        if ($creationToken === null) {
            $creationToken = new CreationToken([
                'token' => uniqid(),
                'email' => strtolower($result->email_address),
                'account_id' => $result->account_id,
                'contact_id' => $result->contact_id,
            ]);
        } else {
            $creationToken->token = uniqid();
        }

        $creationToken->save();

        $language = language($request);

        try {
            Mail::send('emails.basic', [
                'greeting' => trans("emails.greeting",[],$language),
                'body' => trans("emails.accountCreateBody", [
                    'isp_name' => config("app.name"),
                    'portal_url' => config("app.url"),
                    'creation_link' => config("app.url") . "/register/create/" . $creationToken->token,
                ],$language),
                'deleteIfNotYou' => trans("emails.deleteIfNotYou",[],$language),
            ], function ($m) use ($result, $request) {
                $m->from(config("customer_portal.from_address"), config("customer_portal.from_name"));
                $m->to($result->email_address, $result->email_address)
                    ->subject(utrans("emails.createAccount", ['companyName' => config("customer_portal.company_name")],$request));
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(utrans("errors.emailSendFailed",[],$request));
        }

        $this->resetThrottleValue("email_lookup", md5($request->getClientIp()));

        return redirect()->route('login')->with('success', utrans("root.emailFound",[],$request));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function create(string $token, Request $request)
    {
        $creationToken = CreationToken::where('token', '=', trim($token))
            ->where('updated_at', '>=', Carbon::now("UTC")->subHours(24)->toDateTimeString())
            ->first();

        if ($creationToken === null) {
            return redirect()->route('register')->withErrors(utrans("errors.invalidToken",[],$request));
        }

        return view("pages.root.create", compact('creationToken'));
    }

    public function store(AccountCreationRequest $request, string $token): RedirectResponse
    {
        if ($this->getThrottleValue("create_account", md5($token . $request->getClientIp())) > 10) {
            return redirect()->back()->withErrors(utrans("errors.tooManyFailedCreationAttempts",[],$request));
        }

        $creationToken = CreationToken::where('token', '=', trim($token))
            ->where('updated_at', '>=', Carbon::now("UTC")->subHours(24)->toDateTimeString())
            ->first();
        if ($creationToken === null) {
            $this->incrementThrottleValue("email_lookup", md5($token . $request->getClientIp()));
            return redirect()->route('register.create')->withErrors(utrans("errors.invalidToken",[],$request));
        }

        if (strtolower(trim($creationToken->email)) != strtolower(trim($request->input('email')))) {
            $this->incrementThrottleValue("email_lookup", md5($token . $request->getClientIp()));
            return redirect()->back()->withErrors(utrans("errors.invalidEmailAddress",[],$request))->withInput();
        }

        try {
            $this->accountAuthenticationController->createUser($creationToken->account_id, $creationToken->contact_id, $request->input('username'), $request->input('password'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage())->withInput();
        }

        $creationToken->delete();

        $this->resetThrottleValue("email_lookup", md5($token . $request->getClientIp()));
        return redirect()->route('login')->with('success', utrans("register.accountCreated",[],$request));
    }
}