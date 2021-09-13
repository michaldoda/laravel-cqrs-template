<?php

namespace App\Infrastructure\CommandBus;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

class CommandBus implements CommandBusInterface
{
    private $useTransaction = true;
    /**
     * @param Command $command
     * @param bool $useTransaction
     * @return Envelope|null
     * @throws Exception
     */
    public function handle(Command $command, bool $useTransaction = true)
    {
        $this->useTransaction = $useTransaction;
        return $this->execute([$command]);
    }

    /**
     * @param array $commands
     * @param bool $useTransaction
     * @throws Exception
     */
    public function handleMultiple(array $commands, bool $useTransaction = true)
    {
        $this->useTransaction = $useTransaction;
        $this->execute($commands);
    }

    /**
     * @param array $commands
     * @return Envelope|integer
     * @throws Exception
     */
    private function execute(array $commands)
    {
        $executedCommands = [];
        $runtimeException = null;
        $envelopeFromHandler = null;
        try {
            $this->beginTransaction();
            $commandException = null;
            foreach ($commands as $command) {
                try {
                    if (is_null($commandException)) {
                        $handler = App::make($this->getHandler($command));
                        $envelopeFromHandler = $handler($command);
                        $executedCommands[] = $this->generateCommandStats($commands, $command, true, null);
                    } else {
                        $executedCommands[] = $this->generateCommandStats($commands, $command, false, $commandException->getMessage());
                    }
                } catch (Exception $e) {
                    $commandException = $e;
                    $executedCommands[] = $this->generateCommandStats($commands, $command, false, $commandException->getMessage());
                }
            }
            if ($commandException) {
                $this->rollbackTransaction();
                throw $commandException;
            }
            $this->commitTransaction();
        } catch (Exception $e) {
            $runtimeException = $e;
        }

        if ($runtimeException && $this->useTransaction()) {
            foreach ($executedCommands as &$executedCommand) {
                $executedCommand['committed'] = false;
            }
        }

        DB::table('command_bus_log')->insert($executedCommands);

        if ($runtimeException) {
            throw $runtimeException;
        }

        if (count($commands) === 1 && $envelopeFromHandler) {
            return $envelopeFromHandler;
        }

        return 0;
    }

    /**
     * @param Command $command
     * @return string|string[]
     */
    private function getHandler(Command $command)
    {
        $reflection = new ReflectionClass($command);
        $handlerName = str_replace("Command", "Handler", $reflection->getShortName());

        return str_replace($reflection->getShortName(), $handlerName, $reflection->getName());
    }

    private function getCommandParams(Command $command)
    {
        $reflection = new ReflectionClass($command);

        $reflectionProperties = $reflection->getProperties();
        $params = [];
        foreach ($reflectionProperties as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            $params[$reflectionProperty->getName()] = $reflectionProperty->getValue($command);
        }

        return json_encode($params);
    }

    private function generateCommandStats(array $commands, Command $command, bool $isCommitted, $errorMessage): array
    {
        $user = Auth::user();
        return [
            'multiple' => count($commands) > 1,
            'command_name' => get_class($command),
            'command_params' => $this->getCommandParams($command),
            'user_id' => $user ? $user->getAuthIdentifier() : null,
            'committed' => $isCommitted,
            'created_at' => now(),
            'error_message' => $errorMessage,
        ];
    }

    private function getConnectionName(): string
    {
        return DB::getDefaultConnection();
    }

    private function beginTransaction(): void
    {
        if ($this->useTransaction()) {
            DB::connection($this->getConnectionName())->beginTransaction();
        }
    }

    private function commitTransaction(): void
    {
        if ($this->useTransaction()) {
            DB::connection($this->getConnectionName())->commit();
        }
    }

    private function rollbackTransaction(): void
    {
        if ($this->useTransaction()) {
            DB::connection($this->getConnectionName())->rollBack();
        }
    }

    private function useTransaction(): bool
    {
        return $this->useTransaction;
    }
}
