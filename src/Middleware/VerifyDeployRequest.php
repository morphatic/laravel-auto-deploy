<?php

namespace Morphatic\AutoDeploy\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyDeployRequest
{
    protected $origins = [];

    public function __construct(Request $request)
    {
        foreach (config('auto-deploy.origins') as $origin) {
            $type = "\Morphatic\AutoDeploy\Origins\\$origin";
            $this->origins[$origin] = new $type($request);
        }
    }

    /**
     * Handles the HTTP request.
     *
     * @param Illuminate\Http\Request $request The request
     * @param Closure                 $next    Mechanism for passing the result down the pipeline to the next piece of middleware
     *
     * @return Illuminate\Http\Response A Response object that is passed to the next piece of middleware
     */
    public function handle($request, Closure $next)
    {
        if ($request->path() === config('auto-deploy.route')) {
            if (!config('auto-deploy.require-ssl') || $request->secure()) {
                $origin = $this->determineOrigin();
                if (null !== $origin) {
                    if ($origin->isAuthentic()) {
                        // set the origin type in the controller
                        $request->offsetSet('origin', $origin);

                        return $next($request);
                    } else {
                        abort(403, 'Forbidden. Could not verify the origin of the request.');
                    }
                } else {
                    abort(403, 'Forbidden. Could not determine the origin of the request.');
                }
            } else {
                abort(403, 'Forbidden. Webhook requests must be sent using SSL.');
            }
        }
        // Passthrough if it's not our specific route
        return $next($request);
    }

    /**
     * Determine the origin of a deploy request.
     *
     * @return \Morphatic\AutoDeploy\Origins\OriginInterface An object corresponding to the origin type or null.
     */
    private function determineOrigin()
    {
        foreach ($this->origins as $origin) {
            if ($origin->isOrigin()) {
                return $origin;
            }
        }
    }
}
