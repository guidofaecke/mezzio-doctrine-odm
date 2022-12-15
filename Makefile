.PHONY: *

default: unit cs psalm

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

cs: ## verify code style rules
	vendor/bin/phpcs

psalm: ## verify that no static analysis issues were introduced
	vendor/bin/psalm

unit: ## run unit tests
	vendor/bin/phpunit

coverage: ## generate code coverage reports
	vendor/bin/phpunit --testsuite unit --coverage-html build/coverage-html --coverage-text
