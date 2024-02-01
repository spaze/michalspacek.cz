.PHONY: test audit cs-fix check-file-patterns check-makefile lint-php lint-latte lint-neon lint-xml lint-xml-auto-install phpcs phpstan phpstan-latte-templates phpstan-vendor psalm tester tester-include-skipped gitleaks

test: audit check-file-patterns check-makefile lint-php lint-latte lint-neon lint-xml phpcs phpstan tester psalm phpstan-vendor

audit:
	composer audit

cs-fix:
	vendor/bin/phpcbf app/ public/ tests/

check-file-patterns:
	bin/check-file-patterns.sh

check-makefile:
	bin/check-makefile.php

lint-php:
	vendor/php-parallel-lint/php-parallel-lint/parallel-lint --colors -e php,phtml,phpt,phpstub app/ public/ stubs/ tests/

lint-latte:
	bin/latte-lint.php app/

lint-neon:
	vendor/nette/neon/bin/neon-lint app/

lint-xml:
	bin/xmllint.sh

lint-xml-auto-install:
	bin/xmllint.sh --auto-install-with-apt-fast

phpcs:
	vendor/bin/phpcs app/ public/ tests/

phpstan:
	vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan.neon

phpstan-latte-templates:
	vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan-latte-templates.neon

phpstan-vendor:
	vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan-vendor.neon

psalm:
	vendor/bin/psalm.phar

tester:
	vendor/nette/tester/src/tester -s -c tests/php-unix.ini --colors 1 --coverage temp/coverage.html --coverage-src app/ tests/

tester-include-skipped:
	TEST_CASE_RUNNER_INCLUDE_SKIPPED=1 \
	$(MAKE) tester

gitleaks:
	gitleaks detect --verbose --source $(realpath ..)
