<?php

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
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
function getAvailableLanguages($language = "en")
{
    $languages = [];
    $dirs = glob(resource_path("lang/*"));
    foreach ($dirs as $dir)
    {
        $boom = explode("/",$dir);
        if (strlen($boom[count($boom)-1]) === 2)
        {
            $languages[$boom[count($boom)-1]] = trans("languages." . $boom[count($boom)-1],[],$language);
        }
    }
    return $languages;
}

function language(\Illuminate\Http\Request $request = null): string
{
    if ($user = Auth::user()) {
        $language = $user->language();
    } else if ($request && $request->cookie('language')) {
        $language = $request->cookie('language');
    } else {
        $language = Lang::getLocale();
    }
    return $language;
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
