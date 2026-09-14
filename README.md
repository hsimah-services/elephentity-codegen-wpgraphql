# elephentity-codegen-wpgraphql

The WPGraphQL builder for [Elephentity](https://github.com/hsimah-services/elephentity).

It reads one JSON request on stdin — the compiled spec, plus the target's configuration
— and writes one JSON response on stdout: a path and a body per file. It never touches
the filesystem. Signing and writing happen in
[elephentity-codegen](https://github.com/hsimah-services/elephentity-codegen), after this exits.

```bash
echo '{"elephentity":1,"irVersion":"1.1","target":"wpgraphql","config":{},
       "outputDirectory":"out","schema":{}}' | ./bin/eleph-gen-wpgraphql
```

That fails on the empty schema, which is the point: it should be obvious how.

## Installing it

```bash
composer require --dev elephentity/codegen-wpgraphql
```

Then name it in `eleph.json`:

```json
{
  "targets": {
    "wpgraphql": {
      "builder": "vendor/bin/eleph-gen-wpgraphql",
      "output": "generated/wpgraphql"
    }
  }
}
```

Only entities exposed via `integrations: { wpgraphql: {...} }` produce anything; a
project that never declares the integration gets no files, `graphql-manifest.php` and
`verify.php` both.

## What it provides

- The `wpgraphql` integration's declaration — the keys `integrations: { wpgraphql: … }`
  accepts, project-wide and per entity.
- The compiled GraphQL manifest `elephentity/wpgraphql`'s type registrar loads at boot.
- `verify.php`: a ready `Eleph\Runtime\Conformance\Verifier` that `eleph check` loads
  to prove the manifest and the generated PHP classes still agree — reading its own
  manifest off the tree rather than one rebuilt in memory, so it also catches the two
  builders having drifted apart.

## It depends on nothing of Elephentity's

Not the compiler, not the runtime, not the orchestrator. The IR value objects in
`src/Ir` are a copy; the GraphQL manifest shapes (`src/Manifest`) are a second,
independent copy of the same shapes the runtime holds; and the runtime classes the
exported manifest and verifier refer to are strings in `src/Runtime.php` rather than
imports. That is deliberate: a builder that had to `composer require` the framework it
generates for would be a builder no other language could write. The version gate is
what holds the copies in step — a mismatch is a refusal, never a silent misread.

## Working on it

There is no local PHP; everything runs in a container:

```bash
./tools/php composer ci          # style, static analysis, tests
./tools/php vendor/bin/phpunit --filter GoldenTest
```

PHPStan runs at **level max** with no baseline exclusions.

## The golden fixtures

`tests/fixtures/golden/*/` holds a committed request and the exact response it
produces, asserted byte for byte through the real binary. When a deliberate change
moves them, regenerate and read the diff — it is the clearest description available of
what the change did to every project's generated tree.
