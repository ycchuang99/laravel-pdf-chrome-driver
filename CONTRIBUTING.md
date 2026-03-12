# Contributing

## Testing

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) — the test environment runs inside a container that bundles PHP, Chromium, and Xdebug, so no local Chrome installation is needed.
- [GNU Make](https://www.gnu.org/software/make/) — all common tasks are available as `make` targets.

### Quick Start

```bash
# 1. Build the test Docker image (only needed once, or after Dockerfile changes)
make build

# 2. Run the full test suite
make test

# 3. Run tests with coverage (HTML report in coverage/ + Clover XML)
make coverage
```

### All Available Targets

| Target          | Description                                                        |
|-----------------|--------------------------------------------------------------------|
| `make build`    | Build the test Docker image (supports `PHP_VERSION=8.x` override) |
| `make install`  | Install Composer dependencies inside the container                 |
| `make test`     | Run the full Pest test suite                                       |
| `make coverage` | Run tests with Xdebug line coverage (HTML + Clover output)         |
| `make lint`     | Check and auto-fix code style with Laravel Pint                    |
| `make lint-check` | Read-only style check (useful in CI)                             |
| `make analyse`  | Run static analysis with PHPStan                                   |
| `make all`      | Full QA pipeline: `lint → coverage → analyse`                      |
| `make clean`    | Remove the Docker image and generated coverage artefacts           |
| `make shell`    | Open an interactive shell inside the container for debugging       |

### Running Against a Specific PHP Version

```bash
make build PHP_VERSION=8.4
make test
```

### Without Docker

If you have PHP, Composer, and a Chrome/Chromium binary installed locally:

```bash
composer install
vendor/bin/pest                                   # tests only
XDEBUG_MODE=coverage vendor/bin/pest --coverage   # with coverage
vendor/bin/pint                                   # code style
vendor/bin/phpstan analyse                        # static analysis
```

### Test Environment (Dockerfile)

The included `Dockerfile` builds a self-contained test image with:

- Debian Bookworm base
- PHP CLI (configurable via `PHP_VERSION` build-arg, default `8.4`)
- Xdebug (activated via `XDEBUG_MODE=coverage` at runtime)
- Chromium with all required graphics libraries
- Composer 2
- `LARAVEL_PDF_NO_SANDBOX=true` preset for container environments

The project root is mounted into the container at `/app` at run time, so
source changes are picked up immediately without rebuilding the image.
