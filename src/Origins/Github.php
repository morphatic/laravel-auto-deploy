<?php

namespace Morphatic\AutoDeploy\Origins;

class Github extends AbstractOrigin implements OriginInterface
{
    /**
     * The name of the origin.
     *
     * @var string
     */
    public $name = 'Github';

    /**
     * Determines whether or not the Request originated from Github.
     *
     * @param Illuminate\Http\Request $this->request The Request object
     *
     * @return bool Returns true if the request originated from Github. False otherwise.
     */
    public function originated()
    {
        // Correct IP range for Github maintained here:
        // https://help.github.com/articles/what-ip-addresses-does-github-use-that-i-should-whitelist/
        $has_github_header = false !== strpos($this->request->header('User-Agent'), 'GitHub-Hookshot');
        $has_github_ip = $this->ipInRange($this->request->server('REMOTE_ADDR'), '192.30.252.0', 22);
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
     * @param Illuminate\Http\Request $this->request The Request object
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    public function verify()
    {
        // get the Github signature
        $xhub = $this->request->header('X-Hub-Signature') ?: 'nothing';

        // reconstruct the hash on this side
        $hash = 'sha1='.hash_hmac('sha1', $this->request->getContent(), config('auto-deploy.secret'));

        // securely compare them
        return hash_equals($xhub, $hash);
    }

    /**
     * Gets the event the triggered the webhook request.
     *
     * @param Illuminate\Http\Request $this->request The Request object
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    public function event()
    {
        return $this->request->header('X-GitHub-Event');
    }

    /**
     * Gets the URL to be cloned from.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The URL of the repo.
     */
    public function getRepoUrl()
    {
        return $this->request->json('repository.clone_url');
    }

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The commit ID.
     */
    public function getCommitId()
    {
        return $this->request->json('after');
    }
}
