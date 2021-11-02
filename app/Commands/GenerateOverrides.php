<?php

namespace App\Commands;

use App\EzRider\EzRider;
use Wilderborn\Partyline\Facade as Partyline;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class GenerateOverrides extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'generate {config?}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate overrides files for Docker Compose that can automatically generate or fetch sensitive data';

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
            $configFile = $ezRider->loadConfig($configFile);
            $this->info('Ez-rider configuration loaded from: ' . $configFile);
            $ezRider->generateOverrideFiles();
            $this->info('Done.');
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
