<?php

namespace Morphatic\AutoDeploy\Origins;

interface OriginInterface
{
    /**
     * Determines whether or not the Request originated from the webhook origin.
     *
     * @return bool Returns true if the request originated from this origin. False otherwise.
     */
    public function isOrigin();

    /**
     * Verifies the authenticity of a webhook request from the origin.
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    public function isAuthentic();

    /**
     * Gets the event the triggered the webhook request.
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    public function event();

    /**
     * Gets the URL to be cloned from.
     *
     * @return string The URL of the repo.
     */
    public function getRepoUrl();

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @return string The commit ID.
     */
    public function getCommitId();
}
