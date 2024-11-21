.PHONY: test composer-audit npm-audit cs-fix check-file-patterns check-makefile check-sri-macros-concat lint-php lint-latte lint-neon lint-xml lint-xml-auto-install phpcs phpstan phpstan-vendor psalm tester tester-include-skipped gitleaks composer-dependency-analyser update-security-txt

test: composer-audit npm-audit check-file-patterns check-makefile check-sri-macros-concat lint-php lint-latte lint-neon lint-xml phpcs phpstan tester psalm phpstan-vendor composer-dependency-analyser

composer-audit:
	composer audit

npm-audit:
	npm audit

cs-fix:
	vendor/bin/phpcbf src/ public/ tests/

check-file-patterns:
	bin/check-file-patterns.sh

check-makefile:
	bin/check-makefile.php

check-sri-macros-concat:
	bin/check-sri-macros-concat.sh

lint-php:
	vendor/php-parallel-lint/php-parallel-lint/parallel-lint --colors -e php,phtml,phpt,phpstub src/ public/ stubs/ tests/

lint-latte:
	bin/latte-lint.php src/

lint-neon:
	vendor/nette/neon/bin/neon-lint src/

lint-xml:
	bin/xmllint.sh

lint-xml-auto-install:
	bin/xmllint.sh --auto-install-with-apt

phpcs:
	vendor/bin/phpcs src/ public/ tests/

phpstan:
	vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan.neon

phpstan-vendor:
	vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan-vendor.neon

psalm:
	vendor/bin/psalm.phar

tester:
	vendor/nette/tester/src/tester -s -c tests/php-unix.ini -d zend.assertions=1 --colors 1 --coverage temp/coverage.html --coverage-src src/ tests/

tester-include-skipped:
	TEST_CASE_RUNNER_INCLUDE_SKIPPED=1 \
	$(MAKE) tester

gitleaks:
	gitleaks git --verbose $(realpath ..)

composer-dependency-analyser:
	vendor/bin/composer-dependency-analyser --verbose

update-security-txt:
	bin/update-security.txt.sh
