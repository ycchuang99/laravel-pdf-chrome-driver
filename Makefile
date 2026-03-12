# ===========================================================================
# laravel-pdf-chrome-driver – developer Makefile
#
# All targets that run code do so inside the test Docker container so that
# Chrome, PHP, and Xdebug versions are consistent across machines.
#
# Quick-start:
#   make build      # build (or rebuild) the Docker image
#   make test       # run the full test suite
#   make coverage   # run tests with Xdebug coverage + HTML + Clover reports
#   make all        # lint → coverage → analyse (full QA pipeline)
# ===========================================================================

# Image name and optional PHP version override
IMAGE       := laravel-pdf-chrome-test
PHP_VERSION ?= 8.4

# Reusable run command: ephemeral container, project root mounted at /app
DOCKER_RUN := docker run --rm \
    -v "$(CURDIR):/app" \
    -w /app \
    $(IMAGE)

.PHONY: build install test coverage lint analyse all clean shell help

# ---------------------------------------------------------------------------
# help  – list available targets (default goal)
# ---------------------------------------------------------------------------
help:
	@echo ""
	@echo "  laravel-pdf-chrome-driver – available make targets"
	@echo ""
	@echo "  build      Build the test Docker image (PHP $(PHP_VERSION))"
	@echo "  install    Install Composer dependencies inside the container"
	@echo "  test       Run the full Pest test suite"
	@echo "  coverage   Run tests with Xdebug coverage (HTML + Clover output)"
	@echo "  lint       Check and fix code style with Pint"
	@echo "  analyse    Run static analysis with PHPStan"
	@echo "  all        Full QA pipeline: lint → coverage → analyse"
	@echo "  clean      Remove the Docker image and generated artefacts"
	@echo "  shell      Open an interactive shell inside the container"
	@echo ""
	@echo "  Override PHP version:  make build PHP_VERSION=8.4"
	@echo ""

.DEFAULT_GOAL := help

# ---------------------------------------------------------------------------
# build  – create the Docker image
# ---------------------------------------------------------------------------
build:
	docker build \
	    --build-arg PHP_VERSION=$(PHP_VERSION) \
	    -t $(IMAGE) \
	    -f Dockerfile \
	    .

# ---------------------------------------------------------------------------
# install  – run composer install inside the container
# ---------------------------------------------------------------------------
install:
	$(DOCKER_RUN) composer install \
	    --prefer-dist \
	    --no-interaction \
	    --no-progress

# ---------------------------------------------------------------------------
# test  – run the Pest test suite (no coverage)
# ---------------------------------------------------------------------------
test: install
	$(DOCKER_RUN) vendor/bin/pest --colors=always

# ---------------------------------------------------------------------------
# coverage  – run tests with Xdebug line coverage
#             outputs: coverage/ (HTML) and coverage.clover (Clover XML)
# ---------------------------------------------------------------------------
coverage: install
	$(DOCKER_RUN) sh -c "\
	    XDEBUG_MODE=coverage vendor/bin/pest \
	        --colors=always \
	        --coverage \
	"

# ---------------------------------------------------------------------------
# lint  – check and auto-fix code style with Laravel Pint
# ---------------------------------------------------------------------------
lint: install
	$(DOCKER_RUN) vendor/bin/pint

# lint-check  – read-only style check (useful in CI)
lint-check: install
	$(DOCKER_RUN) vendor/bin/pint --test

# ---------------------------------------------------------------------------
# analyse  – static analysis with PHPStan
# ---------------------------------------------------------------------------
analyse: install
	$(DOCKER_RUN) vendor/bin/phpstan analyse --error-format=table

# ---------------------------------------------------------------------------
# all  – full QA pipeline (mirrors CI)
# ---------------------------------------------------------------------------
all: build lint coverage analyse

# ---------------------------------------------------------------------------
# clean  – remove the Docker image and generated artefacts
# ---------------------------------------------------------------------------
clean:
	docker rmi $(IMAGE) 2>/dev/null || true
	rm -rf coverage coverage.clover

# ---------------------------------------------------------------------------
# shell  – interactive shell in the container for ad-hoc debugging
# ---------------------------------------------------------------------------
shell: build
	docker run --rm -it \
	    -v "$(CURDIR):/app" \
	    -w /app \
	    $(IMAGE) bash
