# Composer dependency analyser

- 💪 **Powerful:** Detects unused, shadow and misplaced composer dependencies
- ⚡ **Performant:** Scans 15 000 files in 2s!
- ⚙️ **Configurable:** Fine-grained ignores via PHP config
- 🕸️ **Lightweight:** No composer dependencies
- 🍰 **Easy-to-use:** No config needed for first try
- ✨ **Compatible:** PHP 7.2 - 8.4

## Comparison:

| Project                                   | Dead<br/>dependency | Shadow<br/>dependency  | Misplaced<br/>in `require` | Misplaced<br/> in `require-dev` | Time*      |
|-------------------------------------------|---------------------|------------------------|--------------------------|-------------------------------|------------|
| maglnet/<br/>**composer-require-checker**          | ❌                   | ✅                     | ❌                         |  ❌                             | 124 secs   |
| icanhazstring/<br/>**composer-unused**             | ✅                   | ❌                     | ❌                         |  ❌                             | 72 secs    |
| shipmonk/<br/>**composer-dependency-analyser** | ✅                   | ✅                     | ✅                         |  ✅                             | **2 secs** |

<sup><sub>\*Time measured on codebase with ~15 000 files</sub></sup>

## Installation:

```sh
composer require --dev shipmonk/composer-dependency-analyser
```

*Note that this package itself has **zero composer dependencies.***

## Usage:

```sh
vendor/bin/composer-dependency-analyser
```

Example output:
```txt

Found shadow dependencies!
(those are used, but not listed as dependency in composer.json)

  • nette/utils
    e.g. Nette\Utils\Strings in app/Controller/ProductController.php:24 (+ 6 more)

Found unused dependencies!
(those are listed in composer.json, but no usage was found in scanned paths)

  • nette/utils

(scanned 13970 files in 2.297 s)
```

## Detected issues:
This tool reads your `composer.json` and scans all paths listed in `autoload` & `autoload-dev` sections while analysing you dependencies (both **packages and PHP extensions**).

### Shadowed dependencies
  - Those are dependencies of your dependencies, which are not listed in `composer.json`
  - Your code can break when your direct dependency gets updated to newer version which does not require that shadowed dependency anymore
  - You should list all those packages within your dependencies

### Unused dependencies
  - Any non-dev dependency is expected to have at least single usage within the scanned paths
  - To avoid false positives here, you might need to adjust scanned paths or ignore some packages by `--config`

### Dev dependencies in production code
  - For libraries, this is risky as your users might not have those installed
  - For applications, it can break once you run it with `composer install --no-dev`
  - You should move those from `require-dev` to `require`

### Prod dependencies used only in dev paths
  - For libraries, this miscategorization can lead to uselessly required dependencies for your users
  - You should move those from `require` to `require-dev`

### Unknown classes
  - Any class that cannot be autoloaded gets reported as we cannot say if that one is shadowed or not

### Unknown functions
  - Any function that is used, but not defined during runtime gets reported as we cannot say if that one is shadowed or not

## Cli options:
- `--composer-json path/to/composer.json` for custom path to composer.json
- `--dump-usages symfony/console` to show usages of certain package(s), `*` placeholder is supported
- `--config path/to/config.php` for custom path to config file
- `--version` display version
- `--help` display usage & cli options
- `--verbose` to see more example classes & usages
- `--show-all-usages` to see all usages
- `--format` to use different output format, available are: `console` (default), `junit`
- `--disable-ext-analysis` to disable php extensions analysis (e.g. `ext-xml`)
- `--ignore-unknown-classes` to globally ignore unknown classes
- `--ignore-unknown-functions` to globally ignore unknown functions
- `--ignore-shadow-deps` to globally ignore shadow dependencies
- `--ignore-unused-deps` to globally ignore unused dependencies
- `--ignore-dev-in-prod-deps` to globally ignore dev dependencies in prod code
- `--ignore-prod-only-in-dev-deps` to globally ignore prod dependencies used only in dev paths


## Configuration:
When a file named `composer-dependency-analyser.php` is located in cwd, it gets loaded automatically.
The file must return `ShipMonk\ComposerDependencyAnalyser\Config\Configuration` object.
You can use custom path and filename via `--config` cli option.
Here is example of what you can do:

```php
<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
     //// Adjusting scanned paths
    ->addPathToScan(__DIR__ . '/build', isDev: false)
    ->addPathToExclude(__DIR__ . '/samples')
    ->disableComposerAutoloadPathScan() // disable automatic scan of autoload & autoload-dev paths from composer.json
    ->setFileExtensions(['php']) // applies only to directory scanning, not directly listed files

    //// Ignoring errors
    ->ignoreErrors([ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPath(__DIR__ . '/cache/DIC.php', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/polyfill-php73', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackageAndPath('symfony/console', __DIR__ . '/src/OptionalCommand.php', [ErrorType::SHADOW_DEPENDENCY])

    //// Ignoring unknown symbols
    ->ignoreUnknownClasses(['Memcached'])
    ->ignoreUnknownClassesRegex('~^DDTrace~')
    ->ignoreUnknownFunctions(['opcache_invalidate'])
    ->ignoreUnknownFunctionsRegex('~^opcache_~')

    //// Adjust analysis
    ->enableAnalysisOfUnusedDevDependencies() // dev packages are often used only in CI, so this is not enabled by default
    ->disableReportingUnmatchedIgnores() // do not report ignores that never matched any error
    ->disableExtensionsAnalysis() // do not analyse ext-* dependencies

    //// Use symbols from yaml/xml/neon files
    // - designed for DIC config files (see below)
    // - beware that those are not validated and do not even trigger unknown class error
    ->addForceUsedSymbols($classesExtractedFromNeonJsonYamlXmlEtc)
```

All paths are expected to exist. If you need some glob functionality, you can do it in your config file and pass the expanded list to e.g. `ignoreErrorsOnPaths`.

### Detecting classes from non-php files:

Some classes might be used only in your DIC config files. Here is a simple way to extract those:

```php
$classNameRegex = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*'; // https://www.php.net/manual/en/language.oop5.basic.php
$dicFileContents = file_get_contents(__DIR__ . '/config/services.yaml');

preg_match_all(
    "~$classNameRegex(?:\\\\$classNameRegex)+~", // at least one backslash
    $dicFileContents,
    $matches
); // or parse the yaml properly

$config->addForceUsedSymbols($matches[1]); // possibly filter by class_exists || interface_exists
```

Similar approach should help you to avoid false positives in unused dependencies.
Another approach for DIC-only usages is to scan the generated php file, but that gave us worse results.

### Scanning codebase located elsewhere:
- This can be done by pointing `--composer-json` to `composer.json` of the other codebase

### Disable colored output:
- Set `NO_COLOR` environment variable to disable colored output:
```
NO_COLOR=1 vendor/bin/composer-dependency-analyser
```

## Recommendations:
- For precise `ext-*` analysis, your enabled extensions of your php runtime should be superset of those used in the scanned project

## Contributing:
- Check your code by `composer check`
- Autofix coding-style by `composer fix:cs`
- All functionality must be tested

## Supported PHP versions
- Runtime requires PHP 7.2 - 8.4
- Scanned codebase should use PHP >= 5.3
