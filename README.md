# MigrateSpecific

[![GitHub version](https://badge.fury.io/gh/caloskao%2Fmigrate-specific.svg)](https://badge.fury.io/gh/caloskao%2Fmigrate-specific)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/caloskao/migrate-specific.svg)](https://packagist.org/packages/caloskao/migrate-specific)
[![GitHub issues](https://img.shields.io/github/issues/caloskao/migrate-specific.svg)](https://github.com/caloskao/migrate-specific/issues)
[![GitHub license](https://img.shields.io/github/license/caloskao/migrate-specific.svg)](https://github.com/caloskao/migrate-specific/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/caloskao/migrate-specific.svg)](https://packagist.org/packages/caloskao/migrate-specific)

MigrateSpecific is a Laravel framework CLI extension command that helps you easily perform database migrations of specific migration files.

# Requirement

laravel/framework v5.7 or later.

# Installation

Using [composer](https://getcomposer.org/) to install, run this command in your project root directory:

```
composer require caloskao/migrate-specific
```

Now, run `php artisan` , you can see `migrate:specific` in the migrate section:

```
 migrate
  migrate:fresh        Drop all tables and re-run all migrations
  migrate:install      Create the migration repository
  migrate:refresh      Reset and re-run all migrations
  migrate:rollback     Rollback the last database migration
  migrate:specific     Migrate, refresh, reset or rollback for specific migration files.
  migrate:status       Show the status of each migration
```

# Upgrade from version 1.x

In version 1.x, you may have already registered the command at `app / Console / Kernel.php`:

```
protected $commands = [
    \CalosKao\MigrateSpecific::class
];
```

MigrateSpecific uses [laravel package discovery](https://laravel.com/docs/5.8/packages#package-discovery) to automatically load commands after version 2.0, so you don't need to manually register the commands, but if you have manual registration, you may encounter a class not found exception when you run the application.

To fix this, just remove the class register.

```
protected $commands = [
    \CalosKao\MigrateSpecific::class // remove this line.
];
```

# Usage

You can run `php artisan help migrate:specific` to check command usage:

```
Description:
  Migrate, rollback or refresh for specific migration files.

Usage:
  migrate:specific [options] [--] [<files>...]

Arguments:
  files                          File or directory path, support multiple file (Sperate by space)  [default: "database/migrations"]

Options:
  -p, --pretend                  Dump the SQL queries that would be run
  -f, --skip-foreign-key-checks  Set FOREIGN_KEY_CHECKS=0 before migrate
  -k, --keep-batch               Keep batch number. (Only works in refresh mode)
  -m, --mode[=MODE]              Set migrate execution mode, supported mode have: default, rollback, refresh [default: "default"]
  -y, --assume-yes               Automatically assumes "yes" to run commands in non-interactive mode. This option is automatically enabled if you use the option "-n" or "-q"
  -h, --help                     Display this help message
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --env[=ENV]                The environment the command should run under
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

# Examples

## Migrate single file or directory:

```
php artisan migrate:specific <path>
```

## Migrate mutiple migrations on different paths:

```
php artisan migrate:specific <path 1> <path 2> <path 3> ...
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

Use option `-m` or `--mode` to run `migrate:refresh` or `migrate:rollback` for specific migrations.

```
# Rollback mode
php artisan migrate:specific -m rollback <path>

# Refresh mode
php artisan migrate:specific -m refresh <path>
```

# Keep batch number in refresh mode

Use option `-k` or `--keep-batch` to keep migration batch.

Before migrate:

| Ran?  | Migration                                      | Batch |
|:-----:|------------------------------------------------|:-----:|
| Yes   | 2019_02_14_011711_create_password_resets_table | 1     |
| Yes   | 2019_02_14_011711_create_users_table           | 2     |

```
php artisan migrate:specific -m refresh -k database/migrations/2019_02_14_011711_create_password_resets_table.php
```

Run `php artisan migrate:status` after migrate, you can see the migration `2019_02_14_011711_create_password_resets_table` batch number will not be changed.

# Skip foreign key checks

If your pattern has foreign key constraints, sometimes you might get errors, for example on MySQL or MariaDB:

```
Rolling back: 2019_02_14_011711_create_users_table
SQLSTATE[23000]: Integrity constraint violation: 1451 Cannot delete or update a parent row: a foreign key constraint fails (SQL: drop table `users`)
```

Use option `-f` of `--skip-foreign-key-checks` to execute database statement `SET FOREIGN_KEY_CHECKS=0` before migrate.

**Note:** A good practice is to rollback related foreign key migration at the same time, otherwise you may still get other errors, such as SQL Error 1091.

```
Rolling back: 2019_02_14_011711_create_users_table
SQLSTATE[42000]: Syntax error or access violation: 1091 Can't DROP FOREIGN KEY `fk_projects_users_1`; check that it exists (SQL: alter table `reports` drop foreign key `fk_projects_users_1`)
```

# Skip confirmation

Sometimes we need to perform a database migration many times, or we need to deploy it into an automated process. At this time, we can use the option `-y` to directly perform database migration without confirmation.

```
php artisan migrate:specific -y <path>
```

**Note:**

- If you call the option `-n` or `-q`, MigrateSpecific will be automatically skip confirmation.
- **If you are not working in the above situations, we recommend that you do not perform a database migration in non-interactive mode to avoid accidental data loss.**

# License

The MigrateSpecific extension is open-sourced software licensed under the MIT license.
