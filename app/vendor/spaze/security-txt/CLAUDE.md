# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```sh
composer test                         # full suite: lint, phpcs, phpstan, tester, tester-no-extensions, psalm
composer lint                         # parallel-lint only
composer phpcs                        # code style check
composer cs-fix                       # auto-fix code style
composer phpstan                      # static analysis
composer psalm                        # psalm static analysis
composer tester                       # run all tests (requires curl, gnupg, pcov extensions)
composer tester-include-skipped       # run all tests including the network-dependent ones that are skipped by default (sets TEST_CASE_RUNNER_INCLUDE_SKIPPED=1)
composer tester-no-extensions         # run only the two extension-absence tests
```

Run a single test file:
```sh
vendor/bin/tester -s -c tests/php-unix.ini -d zend.assertions=1 tests/Path/To/TestFile.phpt
```

Run tests that use the internet (skipped by default):
```sh
TEST_CASE_RUNNER_INCLUDE_SKIPPED=1 vendor/bin/tester -s -c tests/php-unix.ini -d zend.assertions=1 --colors 1 tests/
```

## Architecture

The library has three entry points:

1. `SecurityTxtParser::parseString()` — parse raw string content
2. `SecurityTxtParser::parseFetchResult()` — parse a `SecurityTxtFetchResult` fetched elsewhere, useful when fetching and parsing happen in separate processes or services
3. `SecurityTxtCheckHost::check()` — full pipeline with callbacks (what the CLI uses); takes a `Uri\WhatWg\Url` object, not a string

`SecurityTxtWriter` goes the other direction: takes a `SecurityTxt` object and serialises it to a spec-compliant string.

### Key objects

- **`SecurityTxt`** — the main value object; validates field values on set using `SecurityTxtValidationLevel`
- **`SecurityTxtFetcher`** — fetches both `/.well-known/security.txt` and `/security.txt`, handles redirects, enforces SSRF protections (DNS + IP validation, starts from HTTPS URLs and only allows HTTP/HTTPS schemes, 10KB default limit but configurable)
- **`SecurityTxtParser`** — delegates each field to a chain of `FieldProcessor` implementations
- **`SecurityTxtValidator`** — file-level validation (mandatory fields, canonical URI presence, signed-but-no-canonical)
- **`SecurityTxtSignature`** — OpenPGP cleartext signature verification and creation (requires `gnupg` extension)

### Violations

All errors and warnings are `SecurityTxtSpecViolation` subclasses in `src/Violations/`. They carry a human-readable message, a `%s`-placeholder format for safe rendering, and a how-to-fix hint. Line-level violations come from `FieldProcessor` implementations; file-level violations come from `FieldValidator` implementations.

### DNS and HTTP are injectable

`SecurityTxtFetcher` takes `SecurityTxtDnsProvider` and `SecurityTxtFetcherHttpClient` interfaces. This repository includes concrete implementations `SecurityTxtPhpDnsProvider` (uses `dns_get_record()`, no explicit timeout) and `SecurityTxtFetcherCurlClient`; the CLI entrypoint wires these implementations when running the full check pipeline. Tests use anonymous classes implementing these interfaces.

### JSON serialization

`SecurityTxtCheckHostResult` implements `JsonSerializable`. Round-trip deserialization goes through `SecurityTxtJson`, which reconstructs violation objects by class name — validated with `is_subclass_of()` before instantiation.

### Testing

Tests use [Nette Tester](https://tester.nette.org/) (`.phpt` files). Each test file is a standalone PHP script ending with `(new FooTest())->run()`. The `tests/bootstrap.php` sets up the autoloader and provides a `needsInternet()` helper that skips network-dependent tests unless `TEST_CASE_RUNNER_INCLUDE_SKIPPED=1`.

### Constraints

- `parse_url()` is banned — use `Uri\WhatWg\Url` (PHP 8.5 built-in, WhatWG URL standard). Enforced by PHPStan via `phpstan.neon`.
- PHP 8.5 minimum.
- Zero production dependencies.
