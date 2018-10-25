<?php

/*
 * This file is part of the MigrateSpecific package.
 *
 * (c) Calos Kao <calos3257@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CalosKao;

use DB;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateSpecific extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:specific
                            {files* : File path, support multiple file (Sperate by space).}
                            {--m|mode=default : Set migrate exection mode, supported mode have: default, refresh, rollback, new-batch }
                            {--y|assume-yes : Automatic yes to prompts; assume "yes" as answer to all prompts and run non-interactively. The process will be automatic assume yes as answer when  you used option "-n" or "-q". }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate, refresh or reset for specific database migration files.';

    /**
     * Input migration files name.
     *
     * @var string
     */
    private $files;

    /**
     * MigrateSpecific temporary path.
     *
     * @var string
     */
    private $migratePath;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->printHeaderInfo();
        $mode = $this->option('mode');
        if ( !in_array($mode, ['default', 'refresh', 'reset']) ) {
            $this->error("Invalid migrate mode: {$mode}");
            return false;
        }

        $files = $this->argument('files');
        $tmpPath = tempnam(database_path(), 'migrate_file_');
        if ( file_exists($tmpPath) ) {
            unlink($tmpPath);
        }
        mkdir($tmpPath, 0777, true);
        $this->migratePath = str_replace(base_path(), '', $tmpPath);;
        try {
            foreach ($files as $pathSrc) {
                $pathDst = $tmpPath.'/'.basename($pathSrc);
                copy($pathSrc, $pathDst);
            }

            $list = collect(glob("{$tmpPath}/*"));
            $isContinueConfirm = ( $this->option('assume-yes') || $this->option('quiet') || $this->option('no-interaction') );
            if ( false === $isContinueConfirm ) {
                switch ($mode) {
                    case 'reset':   $this->comment('Warning: You have switched to reset mode, which means the migrate:specific command will reset specific migration operations.'.PHP_EOL); break;
                    case 'refresh': $this->comment('Warning: You have switched to refresh mode, which means the migrate:specific command will reset specific migrations and then execute the migrate command.'.PHP_EOL); break;
                }
                $this->line('The following migration files will be migrated:');
                $this->line($list->map(function($v){ return '  '.basename($v); })->implode(PHP_EOL));
                if ( false === $this->confirm('Do you want to continue?') ) {
                    $this->line('Abort.');
                    return false;
                }
            }

            $subCommand = '';
            switch ($mode) {
                default: $this->call('migrate', ['--path' => $this->migratePath]); break;
                case 'reset': $this->line($this->migrate('reset')); break;
                case 'refresh':
                    $newBatchNumber = (int)DB::table('migrations')->max('batch') + 1;
                    $countExistsMigration = DB::table('migrations')
                        ->whereIn('migration', $list->map(function($path){
                            $basename = basename($path);
                            return substr($basename, 0, strrpos($basename, '.'));
                        }))
                        ->update(['batch' => $newBatchNumber]);

                    // If migration status is migrated, reset it first.
                    if ( 0 < $countExistsMigration ) {
                        $this->line($this->migrate('reset'));
                    }
                    $this->call('migrate', ['--path' => $this->migratePath]);
                    break;
            }

        } finally {
            array_map('unlink', glob($tmpPath."/*"));
            rmdir($tmpPath);
        }
    }

    /**
     * Print package infomation.
     *
     * @return void
     */
    private function printHeaderInfo() {
        $this->comment('MigrateSpecific v1.2.0');
        $this->line('Copyright (C) 2018 by CalosKao');
        $this->line('If you have any problem or bug about the use, please come to Github to open the question.');
        $this->info('https://github.com/caloskao/migrate-specific'.PHP_EOL);
    }

    /**
     * Call artisan migrate command.
     *
     * @param  string $mode The artisan migrate command execute mode, avairable option: default, refresh, reset.
     *
     * @return string $value Sub command output.
     */
    private function migrate(string $mode = 'default') {
        $mode = ('default' === $mode ? '' : ":{$mode}");
        return $this->callArtisanBySymfony("migrate{$mode}", ['--path' => $this->migratePath], 'not found');
    }

    /**
     * Call artisan command by Symfony.
     *
     * @param  string $command           Artisan command name.
     * @param  array  $args              Command arguments.
     * @param  string $lineFilterKeyword Filter output line by keyword.
     *
     * @return string Command output.
     */
    private function callArtisanBySymfony(string $command, array $args = [], string $lineFilterKeyword = null) {
        $output = new BufferedOutput;
        $instance = $this->getApplication()->find($command);
        $instance->run(new ArrayInput($args), $output);
        $outputText = $output->fetch();
        if ( null !== $lineFilterKeyword ) {
            $lines = collect(explode(PHP_EOL, $outputText));
            $outputText = $lines->filter(function($item) use ($lineFilterKeyword){
                return ( false === strpos($item, $lineFilterKeyword) );
            })->implode(PHP_EOL);
        }
        return $outputText;
    }
}
