<?php

namespace Morphatic\AutoDeploy\Origins;

abstract class AbstractOrigin
{
    /**
     * Determines whether or not the Request originated from the webhook origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request originated from this origin. False otherwise.
     */
    abstract public function originated($request);

    /**
     * Verifies the authenticity of a webhook request from the origin.
     *
     * @param Illuminate\Http\Request $request The Request object
     *
     * @return bool Returns true if the request is authentic. False otherwise.
     */
    abstract public function verify($request);

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
        if ($cidr_mask <= 0) {
            return false;
        }
        $target = sprintf('%032b', ip2long($target_ip));
        $range = sprintf('%032b', ip2long($range_ip));

        return 0 === substr_compare($target, $range, 0, $cidr_mask);
    }
}
