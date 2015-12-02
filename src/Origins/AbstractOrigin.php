<?php

namespace Morphatic\AutoDeploy\Origins;

use Illuminate\Http\Request;

abstract class AbstractOrigin implements OriginInterface
{
    /**
     * The name of the origin.
     *
     * @var string
     */
    public $name;

    /**
     * The Request object associated with this webhook.
     *
     * @var Illuminate\Http\Request
     */
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Determines whether or not the Request originated from the webhook origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request originated from this origin. False otherwise.
     */
    abstract public function originated();

    /**
     * Verifies the authenticity of a webhook request from the origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    abstract public function verify();

    /**
     * Gets the event the triggered the webhook request.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    abstract public function event();

    /**
     * Gets the URL to be cloned from.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The URL of the repo.
     */
    abstract public function getRepoUrl();

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return string The commit ID.
     */
    abstract public function getCommitId();

    /**
     * Determines if a target IP address is within a particular IP address range.
     *
     * @param string $target_ip The IP address to check
     * @param string $range_ip  The IP address defining the range
     * @param int    $cidr_mask The CIDR notation net mask defining the range
     *
     * @return bool True if the target IP falls within the specified range. Otherwise false.
     */
    protected function ipInRange($target_ip, $range_ip, $cidr_mask)
    {
        $target = sprintf('%032b', ip2long($target_ip));
        $range = sprintf('%032b', ip2long($range_ip));

        return 0 === substr_compare($target, $range, 0, $cidr_mask);
    }
}
