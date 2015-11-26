<?php

Route::post(config('auto-deploy.route'), 'Morphatic\AutoDeploy\DeployController');
//Route::post(config('auto-deploy.route'), 'Morphatic\AutoDeploy\DeployController@index'); // for POST do we need to specify the action?

