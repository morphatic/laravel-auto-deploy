<?php

namespace Morphatic\AutoDeploy\Origins;

interface OriginInterface
{
    /**
     * Determines whether or not the Request originated from the webhook origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request originated from this origin. False otherwise.
     */
    public function originated();

    /**
     * Verifies the authenticity of a webhook request from the origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    public function verify();

    /**
     * Gets the event the triggered the webhook request.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    public function event();

    /**
     * Gets the URL to be cloned from.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The URL of the repo.
     */
    public function getRepoUrl();

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The commit ID.
     */
    public function getCommitId();
}
