.PHONY: test composer-audit composer-update composer-validate composer-outdated npm-audit cs-fix check-file-patterns check-makefile check-sri-macros-concat lint-php lint-latte lint-neon lint-xml lint-xml-auto-install phpcs phpstan phpstan-vendor psalm psalm-clear-cache tester tester-include-skipped gitleaks composer-dependency-analyser update-security-txt

test: composer-audit composer-validate npm-audit check-file-patterns check-makefile check-sri-macros-concat lint-php lint-latte lint-neon lint-xml phpcs phpstan tester psalm phpstan-vendor composer-dependency-analyser

composer-audit:
	composer audit
	composer --working-dir=vendor-dev/ audit

composer-update:
	composer update
	composer --working-dir=vendor-dev/ update

composer-validate:
	composer validate
	composer --working-dir=vendor-dev/ validate --no-check-publish

composer-outdated:
	composer outdated
	composer --working-dir=vendor-dev/ outdated

npm-audit:
	npm audit

cs-fix:
	vendor-dev/vendor/bin/phpcbf src/ public/ tests/ bin/

check-file-patterns:
	bin/check-file-patterns.sh

check-makefile:
	bin/check-makefile.php

check-sri-macros-concat:
	bin/check-sri-macros-concat.sh

lint-php:
	vendor-dev/vendor/php-parallel-lint/php-parallel-lint/parallel-lint --colors -e php,phtml,phpt,phpstub src/ public/ stubs/ tests/

lint-latte:
	bin/latte-lint.php src/

lint-neon:
	# Dirs like vendor and node_modules are excluded by neon-lint
	vendor/nette/neon/bin/neon-lint .

lint-xml:
	bin/xmllint.sh

lint-xml-auto-install:
	bin/xmllint.sh --auto-install-with-apt

phpcs:
	vendor-dev/vendor/bin/phpcs src/ public/ tests/ bin/

phpstan:
	vendor-dev/vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan.neon

phpstan-vendor:
	vendor-dev/vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan-vendor.neon

psalm:
	vendor-dev/vendor/bin/psalm.phar

psalm-clear-cache:
	vendor-dev/vendor/bin/psalm.phar --clear-cache

tester:
	vendor-dev/vendor/nette/tester/src/tester -s -c tests/php-unix.ini -d zend.assertions=1 --colors 1 --coverage temp/coverage.html --coverage-src src/ tests/

tester-include-skipped:
	TEST_CASE_RUNNER_INCLUDE_SKIPPED=1 \
	$(MAKE) tester

gitleaks:
	gitleaks git --verbose $(realpath ..)

composer-dependency-analyser:
	vendor-dev/vendor/bin/composer-dependency-analyser --verbose

update-security-txt:
	bin/update-security.txt.sh
