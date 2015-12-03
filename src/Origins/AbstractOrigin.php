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
     * @return bool Returns true if the request originated from this origin. False otherwise.
     */
    abstract public function isOrigin();

    /**
     * Verifies the authenticity of a webhook request from the origin.
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    abstract public function isAuthentic();

    /**
     * Gets the event the triggered the webhook request.
     *
     * @return string The name of the event, e.g. push, release, create, etc.
     */
    abstract public function event();

    /**
     * Gets the URL to be cloned from.
     *
     * @return string The URL of the repo.
     */
    abstract public function getRepoUrl();

    /**
     * Gets the ID of the commit that is to be cloned.
     *
     * @return string The commit ID.
     */
    abstract public function getCommitId();

    /**
     * Determines if a target IP address is within a particular IP address range.
     *
     * @param string $targetIp The IP address to check
     * @param string $rangeIp  The IP address defining the range
     * @param int    $cidrMask The CIDR notation net mask defining the range
     *
     * @return bool True if the target IP falls within the specified range. Otherwise false.
     */
    protected function isIpInRange($targetIp, $rangeIp, $cidrMask)
    {
        $target = sprintf('%032b', ip2long($targetIp));
        $range = sprintf('%032b', ip2long($rangeIp));

        return 0 === substr_compare($target, $range, 0, $cidrMask);
    }
}
