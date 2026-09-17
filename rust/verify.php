namespace Eleph\WPGraphQL\Verification;

/**
 * eleph check loads this to prove the compiled manifest and the generated
 * classes still agree. Built from the manifest already on disk beside it, so a
 * stale copy of either fails here rather than at the first request.
 *
 * @var \Eleph\WPGraphQL\Manifest\Manifest $manifest
 */
$manifest = require __DIR__ . '/graphql-manifest.php';

return new WPGraphQLVerifier($manifest);
