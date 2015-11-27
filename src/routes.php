<?php

Route::post(
    config('auto-deploy.route'), [
        'as' => 'autodeployroute',
        'uses' => 'Morphatic\AutoDeploy\Controllers\DeployController@index',
    ]
);
