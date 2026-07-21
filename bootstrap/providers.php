<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Acl\Providers\AclServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Company\Providers\CompanyServiceProvider;
use Modules\Project\Providers\ProjectServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    AuthServiceProvider::class,
    AclServiceProvider::class,
    CompanyServiceProvider::class,
    ProjectServiceProvider::class,
];
