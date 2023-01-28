<?php

declare(strict_types=1);

namespace Conia\Core\Commands;

use Conia\Cli\Command;

class InitApp extends Command
{
    public static string $group = 'General';
    public static string $title = 'Initialize the Conia app';

    public function run(): int
    {
        return 0;
    }
}
