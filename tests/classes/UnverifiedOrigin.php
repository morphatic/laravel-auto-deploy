<?php

namespace Morphatic\AutoDeploy\Origins;

class UnverifiedOrigin extends AbstractOrigin implements OriginInterface
{
    /**
     * The name of the origin.
     *
     * @var string
     */
    public $name = 'TestUnverified';

    public function isOrigin()
    {
        return 'Unverified-Webhook' === $this->request->header('ORIGIN');
    }

    public function isAuthentic()
    {
        return false;
    }

    /**
     * Gets the event the triggered the webhook request.
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    public function event()
    {
        return $this->request->header('Event-Type');
    }

    /**
     * Gets the URL to be cloned from.
     *
     * @return string The URL of the repo.
     */
    public function getRepoUrl()
    {
        return 'https://github.com/laravel/framework.git';
    }

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @return string The commit ID.
     */
    public function getCommitId()
    {
        return '8f7d46d9423b334ec63ad4b935c0ce8d8ae1847b';
    }
}
