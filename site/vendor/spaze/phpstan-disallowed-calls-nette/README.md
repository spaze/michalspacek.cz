# phpstan-disallowed-calls-nette

Nette Framework-specific disallowed calls configuration for my Disallowed Calls PHPStan extension, or something.

Prerequisites:
- [PHPStan](https://github.com/phpstan/phpstan)
- [Disallowed Calls PHPStan extension](https://github.com/spaze/phpstan-disallowed-calls)

Installation:

With Composer:
```
composer require --dev spaze/phpstan-disallowed-calls-nette
```

Then include the file in your `phpstan.neon` or similar:
```neon
includes:
    - vendor/spaze/phpstan-disallowed-calls-nette/disallowed-dangerous-calls.neon
```
