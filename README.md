# elephentity-codegen-wpgraphql

The WPGraphQL builder for [Elephentity](https://github.com/hsimah-services/elephentity).

Implemented in Rust. Build a checkout with `cargo build --release --locked`, or install
the executable on PATH with `cargo install --path . --locked`. The `bin/eleph-gen-wpgraphql`
checkout launcher uses `target/release/eleph-gen-wpgraphql` (or a debug build during development).
It never falls back to PHP. Installed Cargo binaries need neither PHP nor Cargo to run.

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
# From this repository:
cargo install --path . --locked

# Or, when using the Composer distribution:
composer require --dev elephentity/codegen-wpgraphql
cargo build --release --locked --manifest-path vendor/elephentity/codegen-wpgraphql/Cargo.toml
```

Use `"builder": "eleph-gen-wpgraphql"` for a Cargo installation, or the Composer
launcher as shown below. Then name it in `eleph.json`:

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

The builder owns its typed wire IR in `rust/ir.rs` and emits runtime class names as
strings. Protocol and IR versions are checked before reading the schema. Static
capability declarations are embedded from `rust/provides.json`; their golden describe
responses ensure the compiler sees the same integration, driver, and pattern contracts.

## Working on it

```bash
cargo fmt --check
cargo clippy --all-targets -- -D warnings
cargo test --locked
./tools/php composer ci
```

Rust sources live in `rust/`. Each builder owns its IR types and version gate; there
is no runtime dependency on the compiler or another builder. PHP in `src/` and the
`bin/eleph-gen-wpgraphql-reference` executable is retained as a migration oracle for the
existing tests. Production entrypoints run Rust only. PHPStan still checks the reference
and acceptance tests at level max.

## The golden fixtures

`tests/fixtures/golden/*/` holds a committed request and the exact response it
produces, asserted byte for byte through the real binary. When a deliberate change
moves them, regenerate and read the diff — it is the clearest description available of
what the change did to every project's generated tree.
