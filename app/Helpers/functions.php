<?php

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

/**
 * Get the authenticated user.
 */
function get_user(): ?Authenticatable
{
    return Auth::user();
}

/**
 * Convert bytes to gigabytes
 * @param $value
 * @return string
 */
function bytes_to_gigabytes($value)
{
    return round($value/1000**4, 2) . "GB";
}

/**
 * Get the configured languages on the system
 * @param string $language
 * @return array
 */
function getAvailableLanguages($translateToLocale = "en")
{
    $languages = [];
    foreach (config('app.available_locales') as $locale) {
        $languages[$locale] = trans("languages." . $locale, [], $translateToLocale);
    }
    return $languages;
}

function language(Request $request = null): string
{
    if ($language = optional(Auth::user())->language()) {
        return $language;
    }

    if ($request->cookie('language')) {
        return $request->cookie('language');
    }

    return Lang::getLocale();
}

/**
 * Translate to the user language
 * @param string $string
 * @param array $variables
 * @param null $request
 * @return \Illuminate\Contracts\Translation\Translator|string
 */
function utrans(string $string, array $variables = [], $request = null)
{
    return trans($string, $variables, language($request));
}

/**
 * Convert expiration date ("12/96") to year and month [12, 96]
 * @param string $date
 * @returns list
 */
function convertExpirationDateToYearAndMonth(string $date)
{
    $date = trim($date);
    $boom = explode("/", $date);

    $month = rtrim($boom[0]);
    $year = ltrim($boom[1]);

    if (strlen($year) == 2) {
        $now = Carbon::now(config("app.timezone"));
        $year = substr($now->year, 0, 2) . $year;
    }

    return array($year, $month);
}
