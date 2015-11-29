<?php

namespace Morphatic\AutoDeploy\Origins;

class UnverifiedOrigin extends AbstractOrigin
{
    public function originated($request)
    {
        return 'Unverified-Webhook' === $request->header('ORIGIN');
    }

    public function verify($request)
    {
        return false;
    }
}
