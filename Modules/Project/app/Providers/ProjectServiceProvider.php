<?php

namespace Modules\Project\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Project\app\Models\Project;
use Modules\Project\app\Models\Task;
use Modules\Project\app\Policies\ProjectPolicy;
use Modules\Project\app\Policies\TaskPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProjectServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Project';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'project';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
