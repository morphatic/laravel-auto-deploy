<?php

namespace Morphatic\AutoDeploy\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DeployController extends Controller
{
    /**
     * Handles incoming webhook requests.
     *
     * @param Request $request The payload from the webhook source, e.g. Github
     */
    public function index(Request $request)
    {
    }
}
