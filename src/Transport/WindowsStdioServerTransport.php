<?php

declare(strict_types=1);

namespace PhpCsFixerMcp\Transport;

use Evenement\EventEmitterTrait;
use PhpMcp\Schema\JsonRpc\Error;
use PhpMcp\Schema\JsonRpc\Message;
use PhpMcp\Schema\JsonRpc\Parser;
use PhpMcp\Server\Contracts\ServerTransportInterface;
use PhpMcp\Server\Exception\TransportException;
use React\Promise\PromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

use Throwable;

final class WindowsStdioServerTransport implements ServerTransportInterface
{
    use EventEmitterTrait;

    private bool $closing = false;

    public function listen(): void
    {
        $this->emit('ready');
        $this->emit('client_connected', ['stdio']);

        while (!$this->closing && ($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            try {
                $message = Parser::parse($line);
                $this->emit('message', [$message, 'stdio', []]);
            } catch (Throwable $exception) {
                $this->sendMessage(Error::forParseError('Invalid JSON: ' . $exception->getMessage()), 'stdio');
            }
        }

        if (!$this->closing) {
            $this->emit('client_disconnected', ['stdio', 'STDIN closed']);
            $this->close();
        }
    }

    public function sendMessage(Message $message, string $sessionId, array $context = []): PromiseInterface
    {
        if ($this->closing) {
            return reject(new TransportException('Windows STDIO transport is closed.'));
        }

        $json = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || fwrite(STDOUT, $json . PHP_EOL) === false) {
            return reject(new TransportException('No se pudo escribir la respuesta MCP.'));
        }

        fflush(STDOUT);

        return resolve(null);
    }

    public function close(): void
    {
        if ($this->closing) {
            return;
        }

        $this->closing = true;
        $this->emit('close', ['Windows STDIO transport closed.']);
        $this->removeAllListeners();
    }
}
