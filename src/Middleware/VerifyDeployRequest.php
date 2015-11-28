<?php

namespace Morphatic\AutoDeploy\Middleware;

use Closure;

class VerifyDeployRequest
{
    protected $origins = [];

    public function __construct()
    {
        foreach (config('auto-deploy.origins') as $origin) {
            $type = "\Morphatic\AutoDeploy\Origins\$origin";
            $this->origins[$origin] = new $type();
        }
    }

    public function handle($request, Closure $next)
    {
        if ($request->path() === config('auto-deploy.route')) {
            if (!config('auto-deploy.require-ssl') || $request->secure()) {
                $origin = $this->determineOrigin($request);
                if (null !== $origin) {
                    if ($origin->verify($request)) {
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
     * @param Illuminate\Http\Request $request The Request object.
     *
     * @return \Morphatic\AutoDeploy\Origins\OriginInterface An object corresponding to the origin type or null.
     */
    private function determineOrigin($request)
    {
        foreach ($this->origins as $origin) {
            if ($origin->originated($request)) {
                return $origin;
            }
        }
    }
}
