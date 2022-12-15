.PHONY: *

default: cs psalm

cs: ## verify code style rules
	vendor/bin/phpcs

psalm:
	vendor/bin/psalm
