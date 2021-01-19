<?php

/*
 * This file is part of the MigrateSpecific package.
 *
 * (c) Calos Kao <calos3257@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CalosKao\MigrateSpecific;

use Illuminate\Support\ServiceProvider;

class MigrateSpecificServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateSpecificCommand::class
            ]);
        }
    }
}