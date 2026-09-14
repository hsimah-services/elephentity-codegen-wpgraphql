<?php

declare(strict_types=1);

namespace Eleph\Gen\WPGraphQL\Verification;

use Eleph\Gen\WPGraphQL\Runtime;

/**
 * Writes the tree's `verify.php`: a ready verifier, not data for `eleph check` to
 * assemble one from.
 *
 * `WPGraphQLVerifier` is named bare because the generated file's own namespace
 * matches where it lives at runtime, the same trick `ManifestExporter` relies on. The
 * manifest it reads back is a different namespace, so that one is a string —
 * `Runtime::MANIFEST` — rather than an import: this repository depends on nothing of
 * Elephentity's. See docs/BUILDERS.md.
 */
final readonly class VerifierExporter
{
    public function export(): string
    {
        return sprintf(
            <<<'PHP'
                namespace Eleph\WPGraphQL\Verification;

                /**
                 * eleph check loads this to prove the compiled manifest and the generated
                 * classes still agree. Built from the manifest already on disk beside it, so a
                 * stale copy of either fails here rather than at the first request.
                 *
                 * @var \%s $manifest
                 */
                $manifest = require __DIR__ . '/graphql-manifest.php';

                return new WPGraphQLVerifier($manifest);

                PHP,
            Runtime::MANIFEST,
        );
    }
}
