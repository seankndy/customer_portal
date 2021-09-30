<?php

namespace App\Http\Controllers;

use App\Actions\SetPortalUserLanguage;
use App\Http\Requests\LanguageUpdateRequest;

class LanguageController extends Controller
{
    public function update(
        LanguageUpdateRequest $request,
        SetPortalUserLanguage $setPortalUserLanguage
    ): \Illuminate\Http\JsonResponse {
        if (get_user()) {
            $setPortalUserLanguage(get_user()->username, $request->language);
        }

        return response()->json([
            'success' => true,
        ])->cookie('language', $request->language, 31536000);
    }
}
