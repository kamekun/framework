<?php

namespace Sokeio\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputOption;

class BLinkCommand extends Command
{
    protected $name = 'b:link';


    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['reload', null, InputOption::VALUE_OPTIONAL, 'reload app.', null],
            ['relative', null, InputOption::VALUE_OPTIONAL, 'Create the symbolic target using relative path.', null],
            ['force', null, InputOption::VALUE_OPTIONAL, 'Recreate existing symbolic targets.', null],
        ];
    }
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the symbolic targets configured for the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Generating optimized symbolic targets.');
        $arr = [config('sokeio.appdir.theme', 'themes'), config('sokeio.appdir.module', 'modules'), config('sokeio.appdir.plugin', 'plugins')];
        $root_path = public_path(config('sokeio.appdir.root', 'platform'));
        if (File::exists($root_path)) {
            File::deleteDirectory($root_path);
        }

        $this->call('storage:link');

        return 0;
    }
    /**
     * Determine if the provided path is a symtarget that can be removed.
     *
     * @param  string  $target
     * @param  bool  $force
     * @return bool
     */
    protected function isRemovableSymtarget(string $target, bool $force): bool
    {
        return is_link($target) && $force;
    }
}
