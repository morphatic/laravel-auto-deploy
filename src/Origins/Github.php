<?php

namespace Morphatic\AutoDeploy\Origins;

use AbstractOrigin;

class Github extends AbstractOrigin
{
    /**
     * Determines whether or not the Request originated from Github.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request originated from Github. False otherwise.
     */
    public function originated($request)
    {
        // Correct IP range for Github maintained here:
        // https://help.github.com/articles/what-ip-addresses-does-github-use-that-i-should-whitelist/
        $has_github_header = false !== strpos($request->header('User-Agent'), 'GitHub-Hookshot');
        $has_github_ip = $this->ipInRange($_SERVER['REMOTE_ADDR'], '192.30.252.0', 22);
        if ($has_github_header && $has_github_ip) {
            return true;
        }

        return false;
    }

    /**
     * Verifies the authenticity of a webhook request from Github.
     *
     * Follows the procedure described here: https://developer.github.com/webhooks/securing/
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    private function verify($request)
    {
        // get the Github signature
        $xhub = $request->header('X-Hub-Signature');

        // reconstruct the hash on this side
        $hash = 'sha1='.hash_hmac('sha1', $request->getContent(), config('auto-deploy.secret'));

        // securely compare them
        return hash_equals($xhub, $hash);
    }
}
