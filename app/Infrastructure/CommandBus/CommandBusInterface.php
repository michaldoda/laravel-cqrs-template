<?php

namespace App\Infrastructure\CommandBus;

interface CommandBusInterface
{
    /**
     * @param Command $command
     * @param bool $useTransaction
     * @return mixed
     */
    public function handle(Command $command, bool $useTransaction = true);

    /**
     * @param array $commands
     * @param bool $useTransaction
     * @return mixed
     */
    public function handleMultiple(array $commands, bool $useTransaction = true);
}
