# Everything runs in the platform-php container. Nothing needs PHP on the host.
DC := docker compose
RUN := $(DC) run --rm php-cli

.DEFAULT_GOAL := help

.PHONY: help
help: ## List targets
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "};{printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

.PHONY: build
build: ## Build the php image
	$(DC) build php-cli

.PHONY: install
install: ## Install composer dependencies
	$(RUN) composer install

.PHONY: up
up: ## Start the whole stack (nats, engine, gateway)
	$(DC) up -d nats engine gateway

.PHONY: spine
spine: ## Start NATS only
	$(DC) up -d nats

.PHONY: logs
logs: ## Follow engine and gateway logs
	$(DC) logs -f engine gateway

.PHONY: down
down: ## Stop everything
	$(DC) down

.PHONY: test
test: spine ## Run the test suite
	$(RUN) vendor/bin/phpunit $(ARGS)

.PHONY: restart
restart: ## Rebuild and restart engine and gateway
	$(DC) restart engine gateway

.PHONY: shell
shell: ## Open a shell in the php container
	$(RUN) bash

.PHONY: php
php: ## Run php, e.g. `make php ARGS="-v"`
	$(RUN) php $(ARGS)

.PHONY: composer
composer: ## Run composer, e.g. `make composer ARGS="require foo/bar"`
	$(RUN) composer $(ARGS)
