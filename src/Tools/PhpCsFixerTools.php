<?php

declare(strict_types=1);

namespace PhpCsFixerMcp\Tools;

use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

final class PhpCsFixerTools
{
    private string $projectRoot;

    public function __construct(
        ?string $projectRoot = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $root = $projectRoot ?? ($_ENV['PHP_CS_FIXER_PROJECT_ROOT'] ?? getcwd());
        $resolvedRoot = realpath($root);

        if ($resolvedRoot === false || !is_dir($resolvedRoot)) {
            throw new \InvalidArgumentException('PHP_CS_FIXER_PROJECT_ROOT debe ser un directorio existente.');
        }

        $this->projectRoot = rtrim($resolvedRoot, DIRECTORY_SEPARATOR);
    }

    #[McpTool(
        name: 'php_cs_fixer_check',
        description: 'Revisa archivos PHP contra PSR-12 sin modificarlos y devuelve las violaciones encontradas.',
    )]
    public function check(
        #[Schema(type: 'string', description: 'Archivo o directorio relativo a PHP_CS_FIXER_PROJECT_ROOT.')]
        string $path = '.',
        #[Schema(type: 'string', description: 'Archivo de configuración .php-cs-fixer.php opcional y relativo al proyecto.')]
        string $config = '',
    ): array {
        return $this->runFixer($path, $config, true);
    }

    #[McpTool(
        name: 'php_cs_fixer_fix',
        description: 'Formatea archivos PHP aplicando PSR-12 y devuelve el resultado del proceso.',
    )]
    public function fix(
        #[Schema(type: 'string', description: 'Archivo o directorio relativo a PHP_CS_FIXER_PROJECT_ROOT.')]
        string $path = '.',
        #[Schema(type: 'string', description: 'Archivo de configuración .php-cs-fixer.php opcional y relativo al proyecto.')]
        string $config = '',
    ): array {
        return $this->runFixer($path, $config, false);
    }

    private function runFixer(string $path, string $config, bool $dryRun): array
    {
        try {
            $target = $this->resolveInsideProject($path);
            $arguments = [PHP_BINARY, $this->phpCsFixerBinary(), $dryRun ? 'check' : 'fix'];

            if ($config !== '') {
                $arguments[] = '--config=' . $this->resolveInsideProject($config);
            } else {
                $arguments[] = '--rules=@PSR12';
            }

            $arguments[] = '--format=json';
            if ($dryRun) {
                $arguments[] = '--diff';
            }
            $arguments[] = $target;

            $process = new Process($arguments, $this->projectRoot);
            $process->setTimeout(120);
            $process->run();

            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());
            $this->logger->info('PHP CS Fixer ejecutado', [
                'path' => $target,
                'dry_run' => $dryRun,
                'exit_code' => $process->getExitCode(),
            ]);

            return [
                'success' => $process->isSuccessful(),
                'exit_code' => $process->getExitCode(),
                'path' => $target,
                'dry_run' => $dryRun,
                'result' => $this->decodeJson($output),
                'output' => $output,
                'error' => $errorOutput,
            ];
        } catch (\Throwable $exception) {
            $this->logger->error('Error ejecutando PHP CS Fixer', ['error' => $exception->getMessage()]);

            return [
                'success' => false,
                'exit_code' => 1,
                'path' => $path,
                'dry_run' => $dryRun,
                'result' => null,
                'output' => '',
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function resolveInsideProject(string $path): string
    {
        $candidate = realpath($path === '.' ? $this->projectRoot : $this->projectRoot . DIRECTORY_SEPARATOR . $path);

        if ($candidate === false || !$this->isInsideProject($candidate)) {
            throw new \InvalidArgumentException('La ruta debe existir y estar dentro de PHP_CS_FIXER_PROJECT_ROOT.');
        }

        return $candidate;
    }

    private function isInsideProject(string $path): bool
    {
        $root = strtolower($this->projectRoot . DIRECTORY_SEPARATOR);
        $normalizedPath = strtolower(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

        return str_starts_with($normalizedPath, $root) || strtolower(rtrim($path, DIRECTORY_SEPARATOR)) === strtolower($this->projectRoot);
    }

    private function phpCsFixerBinary(): string
    {
        $configuredBinary = $_ENV['PHP_CS_FIXER_BINARY'] ?? '';
        if ($configuredBinary !== '') {
            return $configuredBinary;
        }

        $binary = $this->projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-cs-fixer';
        if (is_file($binary)) {
            return $binary;
        }

        $packageBinary = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-cs-fixer';
        if (is_file($packageBinary)) {
            return $packageBinary;
        }

        throw new \RuntimeException('No se encontró vendor/bin/php-cs-fixer. Ejecuta composer install.');
    }

    private function decodeJson(string $output): mixed
    {
        if ($output === '') {
            return null;
        }

        $decoded = json_decode($output, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
