<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->withSession(['auth_password_fingerprint' => hash('sha256', $user->getAuthPassword())]);

        return parent::actingAs($user, $guard);
    }
}
