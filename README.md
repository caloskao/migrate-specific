# MigrateSpecific

[![GitHub version](https://badge.fury.io/gh/caloskao%2Fmigrate-specific.svg)](https://badge.fury.io/gh/caloskao%2Fmigrate-specific)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/caloskao/migrate-specific.svg)](https://packagist.org/packages/caloskao/migrate-specific)
[![GitHub issues](https://img.shields.io/github/issues/caloskao/migrate-specific.svg)](https://github.com/caloskao/migrate-specific/issues)
[![GitHub license](https://img.shields.io/github/license/caloskao/migrate-specific.svg)](https://github.com/caloskao/migrate-specific/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/caloskao/migrate-specific.svg)](https://packagist.org/packages/caloskao/migrate-specific)

MigrateSpecific is a Laravel framework Artisan CLI extension command that helps you easily perform database migrations of specific migration files in the Laravel framework.

# Requirement
laravel/framework v5.0.0 or later.

# Installation

Run command in your Laravel project root directory:

```
composer require caloskao/migrate-specific
```

Register command at `app/Console/Kernel.php` :

```
protected $commands = [
    \CalosKao\MigrateSpecific::class
];
```

Now, run `php artisan` , you can see `migrate:specific` in the migrate section:

```
 migrate
  migrate:fresh        Drop all tables and re-run all migrations
  migrate:install      Create the migration repository
  migrate:refresh      Reset and re-run all migrations
  migrate:reset        Rollback all database migrations
  migrate:rollback     Rollback the last database migration
  migrate:specific     Migrate, refresh, reset or rollback for specific migration files.
  migrate:status       Show the status of each migration
```

# Usage

You can run `php artisan help migrate:specific` to check command usage:

```
Description:
  Migrate, refresh, reset or rollback for specific migration files.

Usage:
  migrate:specific [options] [--] <files>...

Arguments:
  files                 File path, support multiple file. (Sperate by space)

Options:
  -k, --keep-batch      Keep batch number. (Only works in refresh mode)
  -m, --mode[=MODE]     Set migrate execution mode, supported mode have: default, refresh, reset [default: "default"]
  -y, --assume-yes      Automatic yes to prompts; assume "yes" as answer to all prompts and run non-interactively. The process will be automatic assume yes as answer when  you used option "-n" or "-q".
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

# Basic usage

### Migrate single file:

```
php artisan migrate:specific database/migrations/table.php
```

### Migrate mutiple files:

```
php artisan migrate:specific database/migrations/2014_10_12_000000_create_users_table.php /home/caloskao/2018*
```

Output is like below:

```
The following migration files will be migrated:
  2014_10_12_000000_create_users_table.php
  2018_07_31_174401_create_jobs_table.php
  2018_07_31_185911_create_failed_jobs_table.php

 Is this correct? (yes/no) [no]:
 > yes

Migrating: 2014_10_12_000000_create_users_table
Migrated:  2014_10_12_000000_create_users_table
Migrating: 2018_07_31_174401_create_jobs_table
Migrated:  2018_07_31_174401_create_jobs_table
Migrating: 2018_07_31_185911_create_failed_jobs_table
Migrated:  2018_07_31_185911_create_failed_jobs_table
```

# Migrate mode

MigrateSpecific support you execute `migrate:refresh`, `migrate:reset` or `migrate:rollback` command for specific migration file, call `migrate:specific` with option `-m`.

```
# Refresh mode
php artisan migrate:specific -m refresh /path/to/migration.php

# Reset mode
php artisan migrate:specific -m reset /path/to/migration.php

# Rollback mode
php artisan migrate:specific -m rollback /path/to/migration.php
```

# Using MigrateSpecific in non-interactive mode

Sometimes we need to perform a database migration many times, or we need to deploy it into an automated process. At this time, we can use the option `-y` to directly perform database migration without confirmation.

```
php artisan migrate:specific -y /path/to/migration.php
```

**Note:**

 * If you call the option `-n` or `-q`, MigrateSpecific will be automatically enable option `-y`.
 * **If you are not using MigrateSpecific in the above situations, we recommend that you do not perform database migration in non-interactive mode to avoid accidental loss of data.**

# License
The Migrate Specific extension is open-sourced software licensed under the MIT license.
