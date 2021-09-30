<?php

namespace App\Http\Controllers;

use App\Actions\SetPortalUserLanguage;
use App\Http\Requests\LanguageUpdateRequest;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    public function update(
        LanguageUpdateRequest $request,
        SetPortalUserLanguage $setPortalUserLanguage
    ): \Illuminate\Http\JsonResponse {
        if (Auth::user()) {
            $setPortalUserLanguage(Auth::user(), $request->language);
        }

        return response()->json([
            'success' => true,
        ])->cookie('language', $request->language, 31536000);
    }
}
