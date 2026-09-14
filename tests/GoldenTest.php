<?php

declare(strict_types=1);

namespace Eleph\Gen\WPGraphQL\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * The acceptance suite: a committed request in, a committed response out, byte for byte.
 *
 * `tests/fixtures/golden/clog`'s request is the real `examples/clog` schema, encoded
 * exactly as `elephentity`'s compiler would send it — captured from the monorepo
 * builder before this repository existed, so this fixture is also the proof that
 * splitting the builder out changed nothing about what it produces.
 *
 * It runs the real binary rather than calling a class directly, so what is pinned
 * includes the entrypoint: the version gate, the JSON encoding, the exit code and the
 * promise that nothing but the response reaches stdout.
 */
#[CoversNothing]
final class GoldenTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function cases(): array
    {
        return [
            'the worked example, generated' => ['clog'],
            'the worked example, described' => ['describe'],
        ];
    }

    #[DataProvider('cases')]
    public function testTheBuilderReproducesItsFrozenResponse(string $case): void
    {
        $directory = __DIR__ . '/fixtures/golden/' . $case;

        $result = $this->invoke((string) file_get_contents($directory . '/request.json'));

        self::assertSame(0, $result['exit'], $result['stderr']);
        self::assertSame('', $result['stderr'], 'Nothing but the response may be written.');

        self::assertSame(
            json_decode((string) file_get_contents($directory . '/response.json'), true, 512, JSON_THROW_ON_ERROR),
            json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    #[DataProvider('cases')]
    public function testTheResponseIsOneJsonObjectAndNothingElse(string $case): void
    {
        $result = $this->invoke(
            (string) file_get_contents(__DIR__ . '/fixtures/golden/' . $case . '/request.json'),
        );

        self::assertSame(
            $result['stdout'],
            (string) json_encode(json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR), JSON_UNESCAPED_SLASHES),
        );
    }

    public function testARequestFromAnotherIrVersionIsRefusedWithoutGenerating(): void
    {
        $request = $this->request('clog');
        $request['irVersion'] = '99.0';

        $result = $this->invoke((string) json_encode($request));

        self::assertSame(1, $result['exit']);
        self::assertSame('', $result['stdout'], 'A refusal writes nothing to stdout.');
        self::assertStringContainsString('IR version mismatch', $result['stderr']);
    }

    public function testAProjectThatDoesNotSpeakWpgraphqlGetsNoFiles(): void
    {
        $request = $this->request('clog');
        /** @var array<string, mixed> $schema */
        $schema = $request['schema'];
        /** @var array<string, mixed> $project */
        $project = $schema['project'];
        $project['integrations'] = new stdClass();
        $schema['project'] = $project;
        $request['schema'] = $schema;

        $result = $this->invoke((string) json_encode($request));

        self::assertSame(0, $result['exit'], $result['stderr']);

        /** @var array{errors: list<string>, files: list<mixed>} $response */
        $response = json_decode($result['stdout'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([], $response['files'], 'A project not speaking wpgraphql gets no manifest and no verifier.');
        self::assertSame([], $response['errors']);
    }

    public function testDescribeIsRefusedFromAnotherIrVersionToo(): void
    {
        $request = $this->request('describe');
        $request['irVersion'] = '99.0';

        $result = $this->invoke((string) json_encode($request));

        self::assertSame(1, $result['exit']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('IR version mismatch', $result['stderr']);
    }

    public function testAnUnknownRequestNamesWhatItCanAnswer(): void
    {
        $request = $this->request('describe');
        $request['request'] = 'compile';
        unset($request['schema']);

        $result = $this->invoke((string) json_encode($request));

        self::assertSame(1, $result['exit']);
        self::assertStringContainsString('Unknown request "compile"', $result['stderr']);
    }

    public function testNothingOnStdinIsSaidPlainly(): void
    {
        $result = $this->invoke('');

        self::assertSame(1, $result['exit']);
        self::assertStringContainsString('Expected a request on stdin', $result['stderr']);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $case): array
    {
        /** @var array<string, mixed> $request */
        $request = json_decode(
            (string) file_get_contents(__DIR__ . '/fixtures/golden/' . $case . '/request.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        return $request;
    }

    /**
     * @return array{exit: int, stdout: string, stderr: string}
     */
    private function invoke(string $input): array
    {
        $process = proc_open(
            [PHP_BINARY, dirname(__DIR__) . '/bin/eleph-gen-wpgraphql'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Could not run eleph-gen-wpgraphql.');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
