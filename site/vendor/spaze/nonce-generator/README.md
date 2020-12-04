# Nonce Generator

[![PHP Tests](https://github.com/spaze/nonce-generator/workflows/PHP%20Tests/badge.svg)](https://github.com/spaze/nonce-generator/actions?query=workflow%3A%22PHP+Tests%22)

This generates random nonces for Content Security Policy *nonce* attributes. These nonces work with CSP3 [`strict-dynamic`](https://w3c.github.io/webappsec-csp/#strict-dynamic-usage) which aims to make Content Security Policy simpler to deploy for existing applications. This package is intended to be used with [`spaze/csp-config`](https://github.com/spaze/csp-config) and [`spaze/sri-macros`](https://github.com/spaze/sri-macros).

## Usage

This is a *plug and play* generator.

If installed, `\Spaze\ContentSecurityPolicy\Config::addDirective()` from `spaze/csp-config` will automatically add `nonce-...` attribute to configured directives, and [Latte](https://latte.nette.org/) macros `{script ...}` and `{stylesheet ...}` from `spaze/sri-macros` will add `nonce="..."` attribute to `script` and `style` attributes respectively.

## Installation

**With [Composer](https://getcomposer.org):**

```
composer require spaze/nonce-generator
```

**Manual Installation:**

1. Download [the latest stable release](https://github.com/spaze/nonce-generator/releases/latest)
2. Extract the files into your project
3. `require_once '/path/to/spaze/nonce-generator/src/Generator.php';`

## Requirements

- PHP 7.1 or newer

## API

```
getNonce()
```

Generates and returns a nonce. The value of the nonce does not change with multiple `getNonce()` calls but changes when you create new object so the nonce is different for each script execution.
