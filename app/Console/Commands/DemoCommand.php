<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    protected $signature = 'app:demo';

    protected $description = 'Создать (пересоздать) демо-пользователя demo@example.com с историей за 6 недель';

    public function handle(): int
    {
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        $this->info('Демо-пользователь: '.DemoSeeder::EMAIL.' / password');

        return self::SUCCESS;
    }
}
