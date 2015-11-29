<?php

namespace Morphatic\AutoDeploy\Origins;

class VerifiedOrigin extends AbstractOrigin
{
    public function originated($request)
    {
        return 'Verified-Webhook' === $request->header('ORIGIN');
    }

    public function verify($request)
    {
        return true;
    }
}
