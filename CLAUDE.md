# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

Unless noted otherwise, all commands run from the `app/` directory.

```sh
make test                    # full suite: audits, checks, all linters, phpcs, phpstan (+ vendor), tester, psalm, dependency analysis
make composer-update         # update packages in every Composer project of the repository, the Makefile target lists them
make composer-outdated       # check outdated packages in every Composer project of the repository
make phpstan                 # static analysis (level max)
make phpcs                   # code style check
make cs-fix                  # auto-fix code style
make tester                  # run all tests (Nette Tester)
make psalm                   # psalm analysis
make lint-php                # PHP syntax check
make lint-latte              # Latte template lint
make lint-neon               # NEON config file lint
```

Dev tools (PHPStan, Psalm, phpcs, Nette Tester, etc.) live in `dev-tools/` at the repository root, a separate Composer project shared by every project in the repo and kept out of Git. Install before first use:
```sh
composer --working-dir=../dev-tools/ install
```

The repository holds more than one Composer project; the `composer-*` targets in `app/Makefile` are the list of them. A new one goes into every place that enumerates them, or its packages are never audited or updated: the `composer-*` targets in `app/Makefile`, a `lock:` step of the Symfony security checker in `.github/workflows/vulns.yml`, a `composer` entry in `.github/dependabot.yml` when Dependabot is to update it, and a workflow running its `make test` when it has one.

Run a single test file (from `app/`):
```sh
../dev-tools/vendor/nette/tester/src/tester -s -c tests/php-unix.ini -d zend.assertions=1 tests/Path/To/FooTest.phpt
```

Run tests that require Internet (skipped by default):
```sh
make tester-include-skipped
```

## `lambda-security-txt/`

`lambda-security-txt/` is a separate, much smaller Composer project: the security.txt fetching and validation proxy deployed to AWS Lambda with Bref. It has its own `composer.json`, its own committed production `vendor/`, and its own `Makefile` with a reduced target list (`make test` runs `composer audit`, `composer validate`, `lint-php`, `phpcs` and `phpstan`). Its commands run from `lambda-security-txt/`, and it shares the same `dev-tools/`, so it declares no dev dependencies of its own. The `composer-*` targets of `app/Makefile` cover every Composer project of the repository, this one included, so `make composer-update` and the other `composer-*` targets from `app/` take them all at once.

`serverless.yml` ships only an allow-listed subset of `vendor/`, so most of the tree (`symfony/process`, `nyholm/psr7` and the rest of Bref's dependencies) never reaches Lambda. It also excludes `vendor/composer/autoload_files.php`; no production dependency uses `files` autoloading, so Composer doesn't generate that file at all now and the exclusion is currently a no-op. It would need revisiting if a production dependency ever adds a `files` autoload entry, because then the deployed package would quietly skip it.

The `src/*.php` files are handlers that require the autoloader and return a closure rather than declaring classes, so they are themselves neither autoloaded nor namespaced. `make test` and the `PHP tests for lambda-security-txt` workflow cover them; the app's own workflow does not.

## Architecture

### Multi-domain monorepo

`app/public/www.michalspacek.cz/app.php` is the single real entry point. All other domain entry points (e.g., `app/public/admin/app.php`, `app/public/pulse/app.php`) simply include it. The entry point calls `Bootstrap::boot()`, which selects config based on the `SERVER_NAME` server variable (set by the web server, read via `ServerEnv`), loading `app/config/extra-{SERVER_NAME}.neon` as the domain-specific config.

### CLI scripts

PHP scripts in `app/bin/` (e.g., `certmonitor.php`, `latte-lint.php`, `collect_garbage.php`) use `Bootstrap::bootCli()` to boot the DI container, then resolve and run a service. They have access to the same registered services as the web app. The directory also contains shell scripts for tasks that don't need the DI container.

`bootCli()` takes a `CliArgsProvider` class as its argument — this can be the same class that does the work, or a different one. When the running service defines its own args, it's passed to both:

```php
Bootstrap::bootCli(CertificateMonitor::class)->getByType(CertificateMonitor::class)->run();
```

When the script needs no args, pass `NoCliArgs` to `bootCli()` and retrieve the actual service separately:

```php
Bootstrap::bootCli(NoCliArgs::class)->getByType(Makefile::class)->checkAllTargetsArePhony(...);
```

The class passed to `bootCli()` must implement `CliArgsProvider`, which declares what the script accepts on a `Nette\CommandLine\Command` (nette/command-line 2.0) with its `addFlag()`/`addArgument()`/`addOption()` methods, the description as the second argument:

```php
public static function getDescription(): ?string
{
    return 'Checks that every site serves the certificate logged with the API';
}

public static function defineArgs(Command $command): void
{
    $command->addFlag('--no-ipv6', 'Check the certificates over IPv4 only');
    $command->addArgument('path', 'The directory with the templates to lint', normalizer: Normalizers::realPath(...));
}
```

`CliArgs::parse()` builds the command with the provider's `getDescription()` as its description, defines `--help` (also `-h`), `--debug` and `--colors` first (`CliArgs::HELP`, `CliArgs::DEBUG` and `CliArgs::COLORS`, every script takes them), then calls the provider's `defineArgs()`, then parses `$argv`. A provider must not define those again: the command refuses a name it already has with an `InvalidArgumentException`, which is a bug, so nothing catches it and the boot dies.

`--help` is answered by `bootCli()` itself, before the container exists, like `--debug` is read: it prints the help `HelpRenderer` draws from the definitions, the descriptions included, and ends the process with status 0, so no script has any code for it. The flag is standalone, so `latte-lint.php --help` works without the required `<path>`.

A wrong command line (an unknown option, a missing argument, a path that does not exist) is reported there as well: `CliArgs::parse()` throws the library's `ParseException`, `bootCli()` prints its message with the help on STDERR through `HelpRenderer::renderError()` and ends the process with status 2, the usual status for a wrong command line. So the `CliArgs` a service gets always holds a parsed line, and no service or script has code for a wrong one. The report is colored when STDERR takes colors, a terminal without `NO_COLOR` or `FORCE_COLOR`, the way the `console` service decides for STDOUT; `--colors` does not apply to it, the flag would come from the parse that failed.

`bootCli()` registers the result as the `cliArgs` service, a `CliArgs` instance available for injection into any service, with `getFlag(string $name): bool`, `getArg(string $name): string` and `getCommand(): Command`, the definition for `HelpRenderer`. `getFlag()` and `getArg()` throw an `InvalidArgumentException` for a name the provider never defined or defined as another kind, so a typo cannot read as a flag that was not given.

`bootCli()` also registers the `console` service, a `Nette\CommandLine\Console` writing to STDOUT. Color a script's output with its `color()` method, `$this->console->color('red', $text)`: the text comes back wrapped in escape codes when STDOUT is a terminal and `NO_COLOR` is not set, when `FORCE_COLOR` is set, or when `--colors` was given, which wins over `NO_COLOR`; as it is otherwise, and an unknown color name throws either way. Print with `echo`/`printf` like the rest of the script; the console's own `write()` goes straight to the stream, past the output buffering tests capture the output with. Tests pass `new Console(colorDepth: ColorDepth::None)` so the asserted text holds no escape codes.

Tests never build `CliArgs` by hand, they parse an explicit command line against the real definition: `CliArgs::parse(CertificateMonitor::class, ['--no-ipv6'])`; a wrong one throws `ParseException` from `parse()`, which `Assert::exception()` pins.

`exit()` belongs in the script file, not in the service class. Services return values or throw exceptions; the script file handles output and exit codes. The exception is `bootCli()` answering `--help` and reporting a wrong command line, where nothing is running yet that could return a status.

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

The preferred way to pass variables to a template is via a typed parameters class: create a final class (e.g., `InterviewsDefaultTemplateParameters`) with public constructor-promoted properties in the same directory as the presenter, then call `$this->template->setParameters(new InterviewsDefaultTemplateParameters(...))` in the render/action method. The template declares `{templateType ClassName}` at the top (see Latte templates section). PHPStan checks the types where the presenter builds the parameters object; nothing analyses the templates themselves, so the declaration is there for whoever reads or edits the template. Direct `$this->template->varName = value` assignment still works but is not the preferred approach for new code.

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

An empty mask (like `Homepage`) means the presenter handles the domain root. A `Www` presenter whose URL is the same in both languages, like blog posts at `/<slug>` or `/sitemap.xml`, has no entry here; its route has a fixed mask in `RouterFactory::createRouter()`. Routes for other domains (Admin, Api, Pulse, UpcKeys, etc.) are hardcoded there too and do not use `routes.neon`.

### Sitemap

`/sitemap.xml` on the `www` hosts lists the pages of the `Www` module, each with its other-language versions as alternates. Every action of a `Www` presenter carries one of two attributes on its `action` or `render` method, and `SitemapActionsTest` fails for an action with neither, a page with only a template included, which needs a `render` method to carry it, so a new page is a decision rather than something forgotten:

- `#[InSitemap]` for a page any visitor gets at its own address, whether anything on the site links to it or not: the sitemap is for search engines, not the site's navigation. A page without parameters takes the attribute as it is, an action with parameters names a `SitemapEntries` service that lists its pages, `#[InSitemap(FooSitemapEntries::class)]`. One `SitemapEntry` is one page in every language it exists in, so a translated page is a single entry; give it only the languages where the page really exists, or the sitemap claims an alternate that redirects or 404s. List only pages that are their own canonical page: one naming another page as canonical, like a talk showing another talk's slides, is left out.
- `#[NotInSitemap('reason')]` for everything that isn't such a page, with the reason: one standing in for the page asked for, like an error page, one showing what the visitor's session holds, a redirect, a feed or a download.

The `hreflang` alternates in a page's head follow the same attribute: only the page of an `#[InSitemap]` action gets them. A page left out that has a head stands in for another page or shows what the visitor's session holds, which the other language's host doesn't have, so its address in the other language shows something else. Leaving a real page out of the sitemap would take its language versions out of its head too.

The language switcher leads to the page in the other language, or to a list page when there's none. A page that can't be shown in the other language at all, like a training's materials, found with what the visitor's session holds, also takes `#[NoLanguageSwitcher('reason')]` on its `action` or `render` method, and gets no switcher, which would lead to an error there.

The XML is cached per language for a day, and every deploy clears it, so a new page is in as soon as it's deployed and only new content waits. An entry whose link can't be built is logged and left out rather than failing the whole sitemap.

### Naming conventions

Interfaces have no prefix (`I`) and no suffix (`Interface`, `Contract`). The name describes the role; the namespace provides context. The concrete class gets the more specific name:

```
MichalSpacekCz\User\WebAuthn\WebAuthnAuthenticator  ← interface
MichalSpacekCz\User\WebAuthn\PasskeyAuthenticator   ← implementation
```

The exception is Nette's auto-implemented DI factory interfaces, which follow the `*Factory` convention by framework requirement (see below).

### DI factory interfaces

Nette auto-implements factory interfaces. Any interface named `*Factory` with a single `create()` method, when registered in `app/config/services.neon`, gets a generated implementation — inject via constructor, no manual `new`:

```php
interface ArticleHeaderIconsFactory
{
    public function create(): ArticleHeaderIcons;
}
```

```neon
# app/config/services.neon
- MichalSpacekCz\Articles\ArticleHeaderIconsFactory
```

### Forms

Domain-specific form factory classes (`*FormFactory`) build a `Nette\Application\UI\Form` and add their own controls. The forms carry no CSRF token on purpose: before any handler runs, Nette refuses a form submit, which is the form's `submit` signal whatever the form's method, GET search and sort forms included, when the request is not same-origin, going by the `Sec-Fetch-Site` header with the `_nss` SameSite=Strict cookie as the fallback for a browser that doesn't send it. The refusal is `detectedCsrf()`, a redirect back to the same action without the signal, or to the destination of a `CrossOriginRedirectsTo` attribute when the action has one. A form that has to take submits from elsewhere opts out with `allowCrossOrigin()`, so such a call needs its reason next to it. Use `Form::getValues()` in `onSuccess` handlers and `getUntrustedValues()` in `onValidate` handlers.

### Passkey reauthentication

Some admin actions ask the signed-in user to confirm their identity with a passkey again. There are two mechanisms, picked by whether you're guarding a page or a form — `Reauthentication` (`app/src/User/WebAuthn/Authentication/`) holds the shared freshness window (`isFreshAuth()`/`recordFreshAuth()`, TTL from `authentication.passkeys.reauthTtl`).

**Viewing a sensitive page** (e.g. the phpinfo() output): call `$this->requireReauthentication()` (on `Admin\BasePresenter`) at the top of the action. If the user hasn't confirmed within the window it redirects to the `Admin:Reauth` page and brings them back; otherwise it's a no-op. The reauth page must never call it (it would redirect to itself). The freshness window means a just-signed-in user passes without a second prompt.

**Submitting a form that changes something sensitive** (e.g. the account email): confirm in place, with no window — every submit is verified. Two steps:
- In the form factory's `create()`: `$this->passkeyAuthenticationControls->addReauthTo($form);` (adds the hidden credential field, an `onValidate` that verifies the assertion belongs to the current user, and an `onRender` that embeds the prompt's options — minted at render so they match the challenge `verifyAssertion()` checks).
- In the presenter's action/render: `$this->addPermissionsPolicy(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);`. This **must** come from the presenter, not the form: the `Permissions-Policy` header is built on the application's response event, before the template (and the form) renders, so adding it from the form's `onRender` would be too late. (The options are fine in `onRender` — they're a body attribute, sent after the header.)

`PasskeyAuthenticationControls` is shared by every session-based passkey ceremony: sign-in's login form uses `addOptionsTo`, the reauth page and inline forms use `addReauthTo`. One `passkey-authenticate.js` drives them all — prompt on load for the dedicated ceremony pages (sign-in, reauth), prompt on submit for inline forms (the account email), keyed off whether the form carries a `data-canceled-url`. A page gate can't drive a form submit (the redirect can't replay the POST), which is why forms confirm in place instead of being page-gated.

### Database

`TypedDatabase` wraps Nette Database Explorer with methods that return typed values (`fetchFieldString()`, `fetchPairsStringString()`, etc.) and throw on type mismatches. The databases are the keys under `database:` in `app/config/common.neon`, one connection each, and the `typedDatabase.*` services in `app/config/services.neon` say which of them have a `TypedDatabase` wrapper; the rest are read through the connection's `Explorer` directly. Don't copy the list here, the config is the list.

Schema dumps (and a small allow-list of lookup-table data) live in `app/db/*.sql`, one file per database named after it with the `michalspacek_cz_` prefix stripped (`default.sql` for the main database), refreshed by `make dump-db-schema` (which runs `app/bin/dump-db-schema.sh`). Read these instead of guessing — they're the source of truth for column names, types, constraints, and FKs. The dump scrubs `AUTO_INCREMENT=N` and `DEFINER=` clauses so commits don't leak row counts or operator accounts. Data is included only for the tables in the script's `LOOKUP_DATA` list, which is for fixed enumerations — tables with admin CRUD stay schema-only even when they look lookup-shaped (e.g. `url_actions`, `article_sources`).

`ip_ranges` and `ip_ranges_sources` are dumped with data too, though they're seeded copies of third parties' published IP range lists (e.g. Cloudflare's) rather than a fixed enumeration. Shipping an initial set beats an importer that would have to be run before anything worked. The data is public either way, and it's only used to label a host in an error message, so it going stale costs a label, not correctness.

### Database transactions

`beginTransaction()` goes before the try block — if it throws, there is nothing to roll back. The try block covers only the work done inside the transaction:

```php
$this->database->beginTransaction();
try {
    // ... queries ...
    $this->database->commit();
} catch (Exception $e) {
    $this->database->rollBack();
    throw $e;
}
```

### Null checks

Use explicit `if ($x === null)` guards rather than the null coalescing throw (`?? throw`) pattern.

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

`strictParsing` (unknown Latte tags throw a parse error rather than passing through as HTML — catches tag typos) and `strictTypes` (compiled templates include `declare(strict_types=1)`) are enabled. Declare the variables a template uses, with `{varType Type $var}` for individual variables inline or `{templateType ClassName}` for all template parameters in a dedicated class (used in more complex templates); Latte doesn't require it, the declarations document the types for whoever edits the template. Objects implementing `Nette\HtmlStringable` are output raw (unescaped) by Latte — only pass `HtmlStringable` when the content is already safe HTML. Plain strings and `Stringable` objects are always escaped.

### Translations

Translation strings live in `app/src/lang/` as `.neon` files, loaded via `Contributte\Translation`. Two locales are used: `cs_CZ` and `en_US`. The `www` domain serves both (`cs_CZ` at michalspacek.cz, `en_US` at michalspacek.com); other domains serve a single locale each, defined in `app/config/parameters.neon` under `locales.supported`.

Keys are accessed as `$this->translator->translate('messages.section.keyName')`. Sections and key names are camelCase. CLI script messages stay in English (no translation); web UI messages are always translated.

When touching a component that has user-facing strings, translate all strings in that component for consistency. Don't reach into unrelated components to translate their strings in the same change.

### HTTP errors from presenters

Use `throw new BadRequestException($message, $httpCode)` to abort with an HTTP error. Do not use `$this->error()` — it is just a wrapper around `BadRequestException` and the rest of the codebase throws directly.

The `$message` is never sent to the client — it goes to the access log only. The HTTP response uses the translated `messages.error.{code}` string. Developer-readable messages in `BadRequestException` are fine.

### JSON responses from presenters

`$this->sendJson($data)` serializes `$data` to JSON and sends it. If the data is already serialized JSON (e.g. coming from a service that returns a JSON string), use `$this->sendJsonString($json)` from `Www\BasePresenter` instead — it sets the content type and sends a `TextResponse`, avoiding a decode/re-encode round-trip.

### Subresource Integrity

Every JS or CSS file referenced via `{script ...}` or `{style ...}` in a Latte template must also be listed under `subresourceIntegrity.resources` in `app/config/common.neon`.  An unknown resource name is silently treated as a literal string and embedded as-is in the output instead of resolving to the actual file or failing loudly.

### Content Security Policy

CSP policies live in `app/config/contentsecuritypolicy.neon`. When a page needs `fetch()` or other connectivity (e.g. WebAuthn options endpoints), add a `connect-src` entry for that page. The config key is the Nette action name — module, presenter, and action all lowercased with dots, not the URL slug:

```neon
admin.sign.passkeyreset:   # Nette: Admin:Sign:passkeyReset → lowercased, dotted
    @extends: admin.*.*
    connect-src:
        - "'self'"
```

### Texy format filters

Two Latte filters and their `TexyFormatter` PHP equivalents exist for formatting translated strings with substituted arguments:

| Latte filter | PHP method | Use when |
|---|---|---|
| `\|format` | `TexyFormatter::substitute()` / `translate()` | Any arg may be user-controlled — args are HTML-escaped and never interpreted as Texy markup |
| `\|formatPossiblyUnsafeHtml` | `TexyFormatter::substitutePossiblyUnsafeHtml()` / `translatePossiblyUnsafeHtml()` | Args are developer/admin-controlled Texy content (`link:Www:Foo:`, URLs, emails) |

The short name on both sides is the safer variant; the deliberately verbose `PossiblyUnsafeHtml` name marks the unsafe path (React-`dangerouslySetInnerHTML`-style friction). The safe path's marker-based escape supports `%s` placeholders only; numeric specifiers like `%d` silently coerce the marker to 0 (`%s` usage enforced by `app/tests/Formatter/TexyFormatterTranslationPlaceholdersAllowListTest`). Note: `|format` was previously the legacy alias for what is now `|formatPossiblyUnsafeHtml` — that alias was deleted in #747, and the name has now been reused for the safer variant, so PR/issue references predating #747 that mention `|format` mean the opposite of the current behavior.

Note: the filter name describes what **happens** to args (they're passed through to Texy, possibly as markup), not whether the arg itself is dangerous. Emails, URLs, and `link:` directives, even if specified as literals, all need `|formatPossiblyUnsafeHtml` so Texy can recognize and wrap them in `<a>` tags. `|format` HTML-escapes args before Texy sees them, so Texy's auto-detection never fires — fine for plain values like durations and prices, broken for anything you want Texy to turn into a link.

When reviewing templates: flag any `|formatPossiblyUnsafeHtml:$variable` where the variable may be user-controlled. Use `|format` instead. `app/tests/Templating/FiltersFormatPossiblyUnsafeHtmlAllowListTest` enforces this mechanically: any `|formatPossiblyUnsafeHtml` tag with a `$` inside must have an explicit allow-list entry with a reason. The PHP side is covered by `app/disallowed-calls.neon` banning `TexyFormatter::substitutePossiblyUnsafeHtml()` / `translatePossiblyUnsafeHtml()` outside an enumerated allow-list.

`TexyFormatter::createTexy()` restricts `<a href>` schemes to http(s) via `urlSchemeFilters`. Bare emails (`mail@host`) still produce `mailto:` links because Texy's `LinkModule` prepends them before checkURL runs; explicit non-http(s) URLs (`mailto:`, `tel:`, `javascript:`, …) get rejected. `app/tests/Formatter/TexyFormatterAnchorSchemeAllowListTest` scans `.latte` templates and `lang/*.neon` translations for non-http(s) schemes that would land in a Texy-processed `<a href>`, demanding an allow-list entry for each.

### Error and UI messages

Do not end error or UI messages with a full stop (`.`). This applies to short status and error strings (e.g. "Passkey registration failed") — full instructional sentences that are grammatically complete may keep their period.

Use American English spelling: `Canceled` (one L), not `Cancelled`.
