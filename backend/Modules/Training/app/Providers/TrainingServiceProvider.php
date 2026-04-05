<?php

namespace Modules\Training\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Training\Models\GroupParticipant;
use Modules\Training\Observers\GroupParticipantObserver;

class TrainingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Training';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'training';

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


    public function boot(): void
    {
        GroupParticipant::observe(GroupParticipantObserver::class);
    }
    /**
     * Define module schedules.
     * 
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
