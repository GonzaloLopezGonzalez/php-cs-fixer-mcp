<?php

declare(strict_types=1);

namespace PhpCsFixerMcp;

use PhpCsFixerMcp\Tools\PhpCsFixerTools;
use PhpMcp\Server\Server;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class PhpCsFixerMcpServer
{
    public function __construct(private readonly LoggerInterface $logger = new NullLogger())
    {
    }

    public function createServer(): Server
    {
        $tools = new PhpCsFixerTools(logger: $this->logger);
        $container = new class ($tools) implements ContainerInterface {
            public function __construct(private readonly PhpCsFixerTools $tools)
            {
            }

            public function get(string $id): mixed
            {
                if ($id === PhpCsFixerTools::class) {
                    return $this->tools;
                }

                throw new \InvalidArgumentException("Service '{$id}' not found.");
            }

            public function has(string $id): bool
            {
                return $id === PhpCsFixerTools::class;
            }
        };

        return Server::make()
            ->withServerInfo('PHP CS Fixer MCP', '1.0.0')
            ->withLogger($this->logger)
            ->withContainer($container)
            ->withTool(
                [PhpCsFixerTools::class, 'check'],
                'php_cs_fixer_check',
                'Revisa archivos PHP contra PSR-12 sin modificarlos y devuelve las violaciones encontradas.',
            )
            ->withTool(
                [PhpCsFixerTools::class, 'fix'],
                'php_cs_fixer_fix',
                'Formatea archivos PHP aplicando PSR-12 y devuelve el resultado del proceso.',
            )
            ->build();
    }
}
