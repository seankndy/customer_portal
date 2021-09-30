<?php

namespace App\Actions;

use App\User;
use App\UsernameLanguage;

class SetPortalUserLanguage
{
    public function __invoke(User $user, string $language): void
    {
        $usernameLanguage = UsernameLanguage::firstOrNew([
            'username' => $user->username
        ]);
        $usernameLanguage->language = $language;
        $usernameLanguage->save();
    }
}