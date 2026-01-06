<?php

declare(strict_types=1);

namespace B7s\FluentVox\Console;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * FluentVox CLI Application.
 */
class Application extends BaseApplication
{
    public const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct('FluentVox', self::VERSION);

        $this->addCommands([
            new Commands\InstallCommand(),
            new Commands\DoctorCommand(),
            new Commands\ModelsCommand(),
            new Commands\GenerateCommand(),
        ]);
    }
}
