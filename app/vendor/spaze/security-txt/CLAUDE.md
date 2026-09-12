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

A value this library already holds as a `Url` or a `SecurityTxtHost` is passed to a violation or an exception **as that object**, never flattened with `toUnicodeString()` or `getUnicode()` first. Message values are `string|Url|SecurityTxtHost` on both bases, and the base spells a typed one when a result is stored, so the wire is unchanged either way. What changes is the message: `SecurityTxtPrintableValue` percent encodes a bare string, because a string is text a checked host sent, so a flattened host reads `h%C3%A1%C4%8Dky.example` beside an exception naming the same host `háčky.example`. A string is still right for anything read out of the checked file, which is what `SecurityTxtSpecViolation::asUrl()` is for, and for `SecurityTxtCannotParseHostnameException`, which names what failed to become a URL and so takes one even where the thrower holds a `Url`, as `SecurityTxtUrlParser::normalize()` does.

`SecurityTxtWireContractTest` catches a typed value flattened *inside* a class, by building every one with an internationalized host and refusing an encoded spelling in the message. It cannot catch one flattened *before* it arrives, because a constructor taking `string` is indistinguishable from the many that legitimately take a field value. That half is convention, which is why it is written here.

### DNS and HTTP are injectable

`SecurityTxtFetcher` takes `SecurityTxtDnsProvider` and `SecurityTxtFetcherHttpClient` interfaces. This repository includes concrete implementations `SecurityTxtPhpDnsProvider` (uses `dns_get_record()`, no explicit timeout) and `SecurityTxtFetcherCurlClient`; the CLI entrypoint wires these implementations when running the full check pipeline. Tests use anonymous classes implementing these interfaces.

### JSON serialization

`SecurityTxtCheckHostResult` implements `JsonSerializable`. Round-trip deserialization goes through `SecurityTxtJson`, which reconstructs violation objects by class name — validated with `is_subclass_of()` before instantiation.

The format is internal: it exists to hand a result between processes (the Lambda fetcher and the site that called it) and to store one in a cache. It is not published, and nothing outside this library is expected to read fields out of it. It does have to be readable by *another version* of this library, because the two ends deploy separately — that is what `SecurityTxtJson::FORMAT_VERSION` is for, and its docblock says when to bump it.

A serialized exception or violation carries `class` and `params` only. `jsonSerialize()` is deliberately not `final` on the base classes, a subclass writing an extra key harms nothing because the decoder reads those two and ignores the rest, so the shape is what the built-in classes write rather than a guarantee taken from a consumer; `SecurityTxtWireContractTest` asserts it over every one of them. Everything else an object answers is computed by its constructor from those params, so storing it would be a second copy that a reworded message can leave disagreeing with the first, and nothing reads it back to notice. If a consumer wants a historical snapshot of what it displayed, it stores that itself at check time, from `getMessageFormat()` and `getMessageValues()` flattened through `new SecurityTxtPrintableValue($value)->render()` **once**, which is what handles the typed arms. Not twice: `render()` turns a host into `háčky.example` and a plain string into `h%C3%A1%C4%8Dky.example`, so rendering an already-flattened value encodes the readable form it just produced. Escape the stored strings for the medium at display time instead. The library keeps no such record.

### Testing

Tests use [Nette Tester](https://tester.nette.org/) (`.phpt` files). Each test file is a standalone PHP script ending with `new FooTest()->run()`; the parenthesised `(new FooTest())->run()` in older files is legacy, leave it alone rather than churning it. The `tests/bootstrap.php` sets up the autoloader and provides a `needsInternet()` helper that skips network-dependent tests unless `TEST_CASE_RUNNER_INCLUDE_SKIPPED=1`.

When an assertion fails, Tester writes the full actual and expected values to `tests/*/output/FooTest.actual` and `tests/*/output/FooTest.expected` and prints a `diff` command to compare them — use that instead of reading the truncated terminal output. These files are regenerated on every run; do not edit them.

Inline heredoc expected strings in `.phpt` files contain **raw ANSI escape characters** (not `\033[` literals). Text editors and the Edit tool show them as plain `[1;32m` etc., but the bytes are real ESC (0x1b) sequences. Replacing them requires handling the actual bytes — use Python with `ESC = '\033'` and string concatenation rather than text substitution.

### Constraints

- `parse_url()` is banned — use `Uri\WhatWg\Url` (PHP 8.5 built-in, WhatWG URL standard). Enforced by PHPStan via `phpstan.neon`.
- PHP 8.5 minimum.
- Zero production dependencies.
