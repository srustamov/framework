<?php

/**
* @author  Samir Rustamov <rustemovv96@gmail.com>
* @link    https://github.com/srustamov/TT
*/

namespace TT\Engine\Cli;

use TT\Engine\App;
use TT\Facades\DB;

class CreateTables
{
    public static function session($manage)
    {
        $table = false;

        if (isset($manage[ 1 ]) && $manage[ 1 ] === '--create') {
            if (isset($manage[ 2 ]) && !empty($manage[ 2 ])) {
                $table = $manage[ 2 ];
            }
        }
        if (!$table) {
            $table = App::get('config')->get('session.table', 'sessions');
        }

        try {
            DB::exec(static::getSessionTableSql($table));

            new PrintConsole('success', "\n Create session table successfully \n\n");
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S01') {
                new PrintConsole('error', "\n\n {$table} table or view already exists\n");
            } else {
                new PrintConsole('error', "\n\n {$e->getmessage()}\n");
            }

            new PrintConsole('red', "\n \n");
        }
    }



    public static function users()
    {
        try {
            DB::exec(static::getUsersTableSql());

            new PrintConsole('green', "\nUsers table created successfully\n\n");
        } catch (\PDOException $e) {
            if ($e->getCode() === '42S01') {
                new PrintConsole('error', "\n\n users table or view already exists\n");
            } else {
                new PrintConsole('error', "\n\n {$e->getmessage()}\n");
            }
        }
    }



    public static function cache()
    {
        try {
            $table = Config::get('cache.database', ['table' => 'cache'])['table'] ?? 'cache';

            $create = DB::exec("CREATE TABLE IF NOT EXISTS {$table}(
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `cache_key` varchar(255) NOT NULL,
                              `cache_value` longtext NOT NULL,
                              `expires` int(20) NOT NULL DEFAULT '0',
                               PRIMARY KEY (`id`),
                               UNIQUE KEY `cache_key` (`cache_key`)
                              ) DEFAULT CHARSET=utf8
                      ") !== false;

            if ($create) {
                new PrintConsole('success', PHP_EOL . 'Create cache table successfully' . PHP_EOL)
            } else {
                new PrintConsole('error', PHP_EOL . 'Something went wrong' . PHP_EOL);
            }
        } catch (\PDOException $e) {
            throw new \Exception("Create database $table table failed.<br />[".$e->getMessage()."]");
        }
    }



    private static function getSessionTableSql($table): string
    {
        return sprintf('CREATE TABLE IF NOT EXISTS %s (
          `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `expires` int(100) NOT NULL,
          `data` text COLLATE utf8_unicode_ci,
           PRIMARY KEY(`session_id`)
           
         )', $table);
    }


    private static function getUsersTableSql()
    {
        return 'CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                    `password` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                    `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT \'1\',
                    `remember_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `forgotten_pass_code` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `email` (`email`),
                    UNIQUE KEY `remember_token` (`remember_token`)
            )';
    }

}
