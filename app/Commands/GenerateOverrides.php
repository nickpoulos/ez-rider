<?php

namespace App\Commands;

use App\EzRider\EzRider;
use App\EzRider\Plugins\PluginLoader;
use Wilderborn\Partyline\Facade as Partyline;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Illuminate\Contracts\Container\BindingResolutionException;

class GenerateOverrides extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate {config=ezrider.json}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate overrides files for Docker Compose that automatically fetches secrets';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(EzRider $ezRider)
    {
        Partyline::bind($this);
        $configFile = $this->argument('config');

        try {
            $ezRider->loadConfig($configFile);
            $ezRider->generateOverrideFiles();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule)
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
