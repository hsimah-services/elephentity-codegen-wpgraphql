<?php

declare(strict_types=1);

namespace Eleph\Gen\WPGraphQL;

/**
 * The runtime classes the compiled manifest and verifier refer to, named here rather
 * than imported.
 *
 * `return new Manifest(objects: [... new ObjectTypeEntry(...) ...])` type-checks in the
 * project that loads it, and a diff reads as a description of the GraphQL surface. This
 * repository does not have `elephentity/wpgraphql` to import those classes from — by
 * design (docs/BUILDERS.md — "It depends on nothing of Elephentity's"). Most of these
 * are written bare into the generated file, which relies on it declaring the same
 * namespace they live in (`ManifestExporter`); `VerifierExporter` crosses namespaces
 * and interpolates `MANIFEST` as a string for exactly that reason. Either way, this
 * class is the one place that names all of them, so a rename in `elephentity/wpgraphql`
 * has one list to check against rather than a search across templates.
 *
 * If one is renamed there and missed here, nothing on this side fails — this builder
 * still emits the old name, and the project that installed the new runtime fails to
 * load the manifest or the verifier at boot. Regenerating `examples/clog` in
 * `elephentity` is what catches it.
 */
final class Runtime
{
    public const MANIFEST = 'Eleph\WPGraphQL\Manifest\Manifest';
    public const OBJECT_TYPE_ENTRY = 'Eleph\WPGraphQL\Manifest\ObjectTypeEntry';
    public const FIELD_ENTRY = 'Eleph\WPGraphQL\Manifest\FieldEntry';
    public const FIELD_ENCODING = 'Eleph\WPGraphQL\Manifest\FieldEncoding';
    public const CONNECTION_ENTRY = 'Eleph\WPGraphQL\Manifest\ConnectionEntry';
    public const ENUM_TYPE_ENTRY = 'Eleph\WPGraphQL\Manifest\EnumTypeEntry';
    public const MUTATION_ENTRY = 'Eleph\WPGraphQL\Manifest\MutationEntry';
    public const ROOT_FIELD_ENTRY = 'Eleph\WPGraphQL\Manifest\RootFieldEntry';
    public const QUERY_FIELD_ENTRY = 'Eleph\WPGraphQL\Manifest\QueryFieldEntry';
    public const GRAPHQL_TYPE = 'Eleph\WPGraphQL\Manifest\GraphQLType';
    public const WPGRAPHQL_VERIFIER = 'Eleph\WPGraphQL\Verification\WPGraphQLVerifier';
}
