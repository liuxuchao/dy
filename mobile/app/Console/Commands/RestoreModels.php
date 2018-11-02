<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class RestoreModels
 * @package App\Console\Commands
 */
class RestoreModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'restore all models';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = base_path('app/Models/*');
        $files = glob($path);
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $content = file_get_contents($file);
            $content = str_replace('Class Dsc', 'Class ', $content);
            $content = str_replace('class Dsc', 'class ', $content);
            $content = str_replace('table = \'dsc_', 'table = \'', $content);
            $content = str_replace('	public $timestamps', '    public $timestamps', $content);
            file_put_contents($file, $content);
            rename($file, dirname($file) . '/' . str_replace('Dsc', '', $name) . '.php');
        }
    }
}
