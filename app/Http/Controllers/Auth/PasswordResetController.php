<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\SendPasswordResetRequest;
use App\PasswordReset;
use App\Traits\Throttles;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use SonarSoftware\CustomerPortalFramework\Controllers\AccountAuthenticationController;
use SonarSoftware\CustomerPortalFramework\Controllers\ContactController;

class PasswordResetController extends Controller
{
    use Throttles;

    private AccountAuthenticationController $accountAuthenticationController;

    public function __construct(AccountAuthenticationController $accountAuthenticationController)
    {
        $this->accountAuthenticationController = $accountAuthenticationController;
    }

    public function show(): View
    {
        return view("pages.root.reset");
    }

    public function sendResetEmail(SendPasswordResetRequest $request): RedirectResponse
    {
        if ($this->getThrottleValue("password_reset", md5($request->getClientIp())) > 5) {
            return redirect()->back()->withErrors(utrans("errors.tooManyPasswordResetRequests",[],$request));
        }

        try {
            $result = $this->accountAuthenticationController->lookupEmail($request->input('email'), false);
        } catch (\Exception $e) {
            $this->incrementThrottleValue("password_reset", md5($request->getClientIp()));
            return redirect()->back()->withErrors(utrans("errors.resetLookupFailed",[],$request));
        }

        $passwordReset = PasswordReset::where('account_id', '=', $result->account_id)
            ->where('contact_id', '=', $result->contact_id)
            ->first();

        if ($passwordReset === null) {
            $passwordReset = new PasswordReset([
                'token' => uniqid(),
                'email' => $result->email_address,
                'contact_id' => $result->contact_id,
                'account_id' => $result->account_id,
            ]);
        } else {
            $passwordReset->token = uniqid();
        }

        $passwordReset->save();

        $language = language($request);

        try {
            Mail::send('emails.basic', [
                'greeting' => trans("emails.greeting",[],$language),
                'body' => trans("emails.passwordResetBody", [
                    'isp_name' => config("app.name"),
                    'portal_url' => config("app.url"),
                    'reset_link' => config("app.url") . "/reset/" . $passwordReset->token,
                    'username' => $result->username,
                ],$language),
                'deleteIfNotYou' => trans("emails.deleteIfNotYou",[],$language),
            ], function ($m) use ($result, $request) {
                $m->from(config("customer_portal.from_address"), config("customer_portal.from_name"));
                $m->to($result->email_address, $result->email_address);
                $m->subject(utrans("emails.passwordReset", ['companyName' => config("customer_portal.company_name")],$request));
            });
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(utrans("errors.emailSendFailed",[],$request));
        }

        return redirect()->route('login')->with('success', utrans("root.resetSent",[],$request));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showNewPasswordForm(string $token, Request $request)
    {
        $passwordReset = PasswordReset::where('token', '=', $token)
            ->where('updated_at', '>=', Carbon::now("UTC")->subHours(24)->toDateTimeString())
            ->first();

        if ($passwordReset === null) {
            return redirect()->route('login')->withErrors(utrans("errors.resetTokenNotValid",[],$request));
        }

        return view("pages.root.new_password", compact('passwordReset'));
    }

    public function resetPassword(PasswordUpdateRequest $request, string $token): RedirectResponse
    {
        if ($this->getThrottleValue("password_update", md5($request->getClientIp())) > 5) {
            return redirect()->back()->withErrors(utrans("errors.tooManyFailedPasswordResets",[],$request));
        }

        $passwordReset = PasswordReset::where('token', '=', trim($token))
            ->where('updated_at', '>=', Carbon::now("UTC")->subHours(24)->toDateTimeString())
            ->first();
        if ($passwordReset === null) {
            $this->incrementThrottleValue("password_update", md5($token . $request->getClientIp()));
            return redirect()->route('reset-password.new')->withErrors(utrans("errors.invalidToken",[],$request));
        }

        if ($passwordReset->email != $request->input('email')) {
            $this->incrementThrottleValue("password_update", md5($token . $request->getClientIp()));
            return redirect()->back()->withErrors(utrans("errors.invalidEmailAddress",[],$request));
        }

        $contactController = new ContactController();
        try {
            $contact = $contactController->getContact($passwordReset->contact_id, $passwordReset->account_id);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(utrans("errors.couldNotFindAccount",[],$request));
        }
        try {
            $contactController->updateContactPassword($contact, $request->input('password'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(utrans("errors.failedToResetPassword",[],$request));
        }

        $passwordReset->delete();

        $this->resetThrottleValue("password_update", md5($token . $request->getClientIp()));
        return redirect()->route('login')->with('success', utrans("register.passwordReset",[],$request));
    }
}