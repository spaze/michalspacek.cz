# Nonce Generator

[![PHP Tests](https://github.com/spaze/nonce-generator/workflows/PHP%20Tests/badge.svg)](https://github.com/spaze/nonce-generator/actions?query=workflow%3A%22PHP+Tests%22)

This generates random nonces for Content Security Policy *nonce* attributes. These nonces work with CSP3 [`strict-dynamic`](https://w3c.github.io/webappsec-csp/#strict-dynamic-usage) which aims to make Content Security Policy simpler to deploy for existing applications. This package is intended to be used with Nette Framework, [`spaze/csp-config`](https://github.com/spaze/csp-config) and [`spaze/sri-macros`](https://github.com/spaze/sri-macros).

## Usage

This is a *plug and play* generator.

If installed, `\Spaze\ContentSecurityPolicy\Config::addDirective()` from `spaze/csp-config` will automatically add `nonce-...` attribute to configured directives, and [Latte](https://latte.nette.org/) macros `{script ...}` and `{stylesheet ...}` from `spaze/sri-macros` will add `nonce="..."` attribute to `script` and `style` attributes respectively. Also `n:nonce` [shortcut](https://doc.nette.org/en/http/configuration#toc-content-security-policy) will use the same generated  value.

## Installation

With [Composer](https://getcomposer.org):

```
composer require spaze/nonce-generator
```

Add the extension to your configuration:

```neon
extensions:
	nonceGenerator: Spaze\NonceGenerator\Bridges\Nette\GeneratorExtension
```

## Requirements

- PHP 8.2 or newer
- Latte 3.0 or newer
- Nette Application 3.1 or newer
- Nette DI 3.0 or newer

## API

```
createNonce(): Nonce
```
Generates and returns a `Nonce` object. Use `Nonce::getValue()` to get the generated nonce.
