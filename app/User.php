<?php

namespace App;

use App\SonarApi\Resources\Contact;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Represents a Sonar Customer Portal user which is actually a "Contact" in Sonar itself.
 *
 */
class User implements Authenticatable
{
    use Notifiable;

    public int $id;

    public int $accountId;

    public string $name;

    public string $username;

    public string $emailAddress;

    public static function fromSonarContactResource(Contact $contact): self
    {
        if ($contact->contactableType !== 'Account') {
            throw new \Exception("Only Account Contacts are allowed.");
        }

        $user = new self();
        $user->id = $contact->id;
        $user->name = $contact->name;
        $user->username = $contact->username;
        $user->emailAddress = $contact->emailAddress;
        $user->accountId = $contact->contactableId;

        return $user;
    }

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthPassword()
    {
        return null;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        //
    }

    public function getRememberTokenName()
    {
        return null;
    }
}
