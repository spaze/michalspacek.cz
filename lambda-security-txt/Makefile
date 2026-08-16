.PHONY: test composer-audit composer-update composer-validate composer-outdated cs-fix lint-php phpcs phpstan deploy-dev deploy-prod

test: composer-audit composer-validate lint-php phpcs phpstan

composer-audit:
	composer audit
	composer --working-dir=../dev-tools/ audit

composer-update:
	composer update
	composer --working-dir=../dev-tools/ update

composer-validate:
	composer validate
	composer --working-dir=../dev-tools/ validate --no-check-publish

composer-outdated:
	composer outdated
	composer --working-dir=../dev-tools/ outdated

cs-fix:
	../dev-tools/vendor/bin/phpcbf src/

lint-php:
	../dev-tools/vendor/php-parallel-lint/php-parallel-lint/parallel-lint --colors -e php src/

phpcs:
	../dev-tools/vendor/bin/phpcs src/

phpstan:
	../dev-tools/vendor/phpstan/phpstan/phpstan -vvv --ansi analyse --configuration phpstan.neon

deploy-dev:
	serverless deploy --stage=dev

deploy-prod:
	serverless deploy --stage=prod
