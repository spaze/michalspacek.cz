# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run from the `app/` directory.

```sh
make test                    # full suite: audits, checks, all linters, phpcs, phpstan (+ vendor), tester, psalm, dependency analysis
make phpstan                 # static analysis (level max)
make phpcs                   # code style check
make cs-fix                  # auto-fix code style
make tester                  # run all tests (Nette Tester)
make psalm                   # psalm analysis
make lint-php                # PHP syntax check
make lint-latte              # Latte template lint
make lint-neon               # NEON config file lint
```

Run a single test file (from `app/`):
```sh
vendor/nette/tester/src/tester -s -c tests/php-unix.ini -d zend.assertions=1 tests/Path/To/FooTest.phpt
```

Run tests that require Internet (skipped by default):
```sh
make tester-include-skipped
```

## Architecture

### Multi-domain monorepo

`app/public/www.michalspacek.cz/app.php` is the single real entry point. All other domain entry points (e.g., `app/public/admin/app.php`, `app/public/pulse/app.php`) simply include it. The entry point calls `Bootstrap::boot()`, which selects config based on the `SERVER_NAME` server variable (set by the web server, read via `ServerEnv`), loading `app/config/extra-{SERVER_NAME}.neon` as the domain-specific config.

### CLI scripts

PHP scripts in `app/bin/` (e.g., `certmonitor.php`, `latte-lint.php`, `clean_sessions.php`) use `Bootstrap::bootCli()` to boot the DI container, then resolve and run a service. They have access to the same registered services as the web app. The directory also contains shell scripts for tasks that don't need the DI container.

`bootCli()` takes a `CliArgsProvider` class as its argument — this can be the same class that does the work, or a different one. When the running service defines its own args, it's passed to both:

```php
Bootstrap::bootCli(CertificateMonitor::class)->getByType(CertificateMonitor::class)->run();
```

When the script needs no args, pass `NoCliArgs` to `bootCli()` and retrieve the actual service separately:

```php
Bootstrap::bootCli(NoCliArgs::class)->getByType(Makefile::class)->checkAllTargetsArePhony(...);
```

The class passed to `bootCli()` must implement `CliArgsProvider`, which declares what CLI arguments the script accepts via two static methods:

```php
public static function getArgs(): array          // flag names, e.g. ['--no-ipv6']
public static function getPositionalArgs(): array // positional args, using Nette\CommandLine\Parser constants
```

`bootCli()` automatically adds `--debug` and `--colors` to every script's arg list on top of whatever the provider declares. It parses `$argv` against these definitions and registers the result as a `CliArgs` instance in the DI container, available for injection into any service. `CliArgs` provides `getFlag(string $arg): bool`, `getArg(string $arg): string`, and `getError(): ?string`. `getError()` is non-null when `$argv` parsing failed (e.g. an unknown flag was passed); services should check it before using the args. Scripts that accept no arguments pass `NoCliArgs` (returns empty arrays from both methods).

### Config loading order

`Bootstrap::boot()` loads these in sequence (later files win):
1. `app/config/extensions.neon` — third-party DI extensions
2. `app/config/common.neon` — app-wide Nette/PHP settings
3. `app/config/contentsecuritypolicy.neon` — CSP directives, organized by domain and reusable snippets
4. `app/config/parameters.neon` — locale definitions, domain routing tables, and other app-wide parameters
5. `app/config/presenters.neon` — registers all presenter classes as DI services
6. `app/config/services.neon` — all application services
7. `app/config/routes.neon` — translated route masks per locale
8. `app/config/extra-{SERVER_NAME}.neon` — domain-specific overrides; these files use neon `includes:` to pull in the corresponding `app/config/common-{domain}.neon` (e.g., `extra-michalspacek.cz.neon` includes `common-michalspacek.cz.neon`)
9. `app/config/local.neon` — local dev overrides (not committed; copy from `app/config/local.template.neon`)

CLI scripts use `Bootstrap::bootCli()` which loads `extra-cli.neon` (or `extra-cli-debug.neon` when `PHP_CLI_ENVIRONMENT=development` or `--debug` is passed) in place of `extra-{SERVER_NAME}.neon`; steps 1–7 and `local.neon` still load.

Tests use `Bootstrap::bootTest()` which loads `app/config/tests.neon` as the final config in place of the domain-specific `extra-*.neon` — steps 1–7 and `local.neon` still load, but `tests.neon` wins over them. `tests.neon` overrides services for testing: mocks sleep (`Insomnia`), time (`DateTimeMachineFactory`), HTTP request/response, mail, translation, and logging; routes all three databases to a single shared test `Database` instance; disables SRI integrity checks; uses null cache storage (`DevNullStorage`).

### Presenter mapping

Nette mapping rule: `*: MichalSpacekCz\Presentation\*\**Presenter`

In the right-side template, `*` is the module name and `**` expands the presenter name into both a sub-namespace and the class name prefix. `Www:Talks` → `MichalSpacekCz\Presentation\Www\Talks\TalksPresenter`

Presenters live in `app/src/Presentation/{Module}/{Presenter}/`, alongside their Latte templates (`*.latte`). Components (reusable UI pieces) extend `UiControl` and live alongside their domain service classes (e.g., `app/src/Talks/TalkInputs.php` + `talkInputs.latte`).

The preferred way to pass variables to a template is via a typed parameters class: create a final class (e.g., `InterviewsDefaultTemplateParameters`) with public constructor-promoted properties in the same directory as the presenter, then call `$this->template->setParameters(new InterviewsDefaultTemplateParameters(...))` in the render/action method. The template declares `{templateType ClassName}` at the top (see Latte templates section), which gives PHPStan full type visibility into template variables. Direct `$this->template->varName = value` assignment still works but is not the preferred approach for new code.

### Translated routes

`app/config/routes.neon` maps `Www` module presenter names and action names to locale-specific URL masks. When adding a new presenter or action on the `www` domain, add an entry for both `cs_CZ` and `en_US`:

```neon
parameters:
    translatedRoutes:
        presenters:
            Www:
                MyPresenter:
                    mask:
                        cs_CZ: moje-stranka
                        en_US: my-page
                    actions:
                        detail:
                            cs_CZ: detail
                            en_US: detail
```

An empty mask (like `Homepage`) means the presenter handles the domain root. Routes for other domains (Admin, Api, Pulse, UpcKeys, etc.) are hardcoded in `RouterFactory::createRouter()` and do not use `routes.neon`.

### DI factory interfaces

Nette auto-implements factory interfaces. Any interface named `*Factory` with a single `create()` method, when registered in `app/config/services.neon`, gets a generated implementation — inject via constructor, no manual `new`:

```php
interface FormValidatorRuleTexyFactory
{
    public function create(): FormValidatorRuleTexy;
}
```

```neon
# app/config/services.neon
- MichalSpacekCz\Form\Validators\FormValidatorRuleTexyFactory
```

### Forms

`FormFactory::create()` returns a `UiForm` (extends `Nette\Application\UI\Form`) with CSRF protection always added. `UnprotectedFormFactory` exists for forms that intentionally omit CSRF (e.g. GET search/sort forms, theme toggles, honeypot traps). Domain-specific form factory classes (`*FormFactory`) inject one of these and add their own controls. Use `UiForm::getFormValues()` in `onSuccess` handlers and `getUntrustedFormValues()` in `onValidate` handlers — raw `Container::getValues()` and `Container::getUntrustedValues()` are both blocked by PHPStan.

### Database

`TypedDatabase` wraps Nette Database Explorer with methods that return typed values (`fetchFieldString()`, `fetchPairsStringString()`, etc.) and throw on type mismatches. Three databases, each with its own `TypedDatabase` service instance:
- `default` — main application data (talks, training, etc.)
- `pulse` — password storage security ratings (companies, algorithms, disclosures)
- `upcKeys` — WiFi router default key data (Technicolor, Ubee)

### Testing

`TestCaseRunner::run(FooTest::class)` boots a full DI container via `Bootstrap::bootTest()` and resolves constructor parameters from the container by type. Tests are plain `Tester\TestCase` subclasses — no mocking framework, test doubles are hand-written classes in `app/src/Test/`. Test files live in `app/tests/` and use the `.phpt` extension.

### PHPStan-enforced API restrictions (`app/disallowed-calls.neon`)

PHPStan at level max with strict rules. Project-specific restrictions:

| Banned | Use instead |
|--------|-------------|
| `pcntl_*()` | (banned entirely) |
| `rand()`, `uniqid()` | `random_int()`, `random_bytes()` |
| `preg_*()` (except `preg_quote`) | `Composer\Pcre\Preg` / `Composer\Pcre\Regex` |
| `array_filter()` without callback | `MichalSpacekCz\Utils\Arrays::filterEmpty()` |
| `parse_url()` | `Uri\WhatWg\Url` |
| `Uri\Rfc3986\Uri` | `Uri\WhatWg\Url` (WHATWG standard is what browsers follow; RFC 3986 is not) |
| `setcookie()`, `getCookie()`, `setCookie()`, `deleteCookie()` | `MichalSpacekCz\Http\Cookies\Cookies` |
| `getPost()` | `MichalSpacekCz\Http\HttpInput` |
| `$_SERVER` | `MichalSpacekCz\Application\ServerEnv` |
| `DateTimeZone::__construct()` | `MichalSpacekCz\DateTime\DateTimeZoneFactory::get()` |
| `Nette\Utils\Strings::match/matchAll/replace/split()` | `Composer\Pcre\Preg` / `Composer\Pcre\Regex` |
| `Tester\Environment::skip()` directly | `TestCaseRunner::needsInternet()` for internet-requiring tests; add a new method to `TestCaseRunner` for other skip reasons |
| `Spaze\PhpInfo\PhpInfo` | `MichalSpacekCz\Application\SanitizedPhpInfo` |
| `LIBXML_NOENT` | (banned entirely) |

### Latte templates

`strictParsing` (unknown Latte tags throw a parse error rather than passing through as HTML — catches tag typos) and `strictTypes` (compiled templates include `declare(strict_types=1)`) are enabled. With `strictTypes`, template variables must be declared: use `{varType Type $var}` for individual variables inline, or `{templateType ClassName}` to declare all template parameters in a dedicated class (used in more complex templates). Objects implementing `Nette\HtmlStringable` are output raw (unescaped) by Latte — only pass `HtmlStringable` when the content is already safe HTML. Plain strings and `Stringable` objects are always escaped.

### Translations

Translation strings live in `app/src/lang/` as `.neon` files, loaded via `Contributte\Translation`. Two locales are used: `cs_CZ` and `en_US`. The `www` domain serves both (`cs_CZ` at michalspacek.cz, `en_US` at michalspacek.com); other domains serve a single locale each, defined in `app/config/parameters.neon` under `locales.supported`.
