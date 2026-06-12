# Contributing

Thank you for contributing to this WP Desk package.

This project is maintained as a public PHP Composer package. Keep changes small,
clear, and covered by the checks that apply to this repository.

## Before you start

- Check existing issues and pull requests to avoid duplicate work.
- Open an issue first for larger changes, behavior changes, or public API changes.
- Keep pull requests focused on one problem.
- Do not include unrelated refactoring, formatting, or generated files.

## Local setup

```sh
composer install
```

If the package integrates with WordPress, WooCommerce, or another plugin, check
the README and project documentation for any additional local requirements.

## Quality checks

Run the checks that exist in `composer.json` before opening a pull request.

Common commands are:

```sh
composer test
composer analyse
composer lint
```

Some repositories may use only part of this set. If a command is missing, do not
add it just for your pull request unless the change explicitly introduces that
tooling.

## Coding standards

- Follow the coding style already used in the repository.
- Prefer simple, explicit code over broad abstractions.
- Keep public APIs backward compatible unless the change is intentionally
  breaking and has been discussed.
- Update documentation when behavior, configuration, or installation changes.
- Add or update tests for bug fixes and behavior changes.

## Pull request checklist

Before submitting, verify:

- The change solves a specific problem.
- Tests pass locally, or the reason they cannot be run is explained.
- Static analysis and coding standards pass when configured.
- Public-facing documentation is updated when needed.
- The pull request description explains the user-visible impact.

## Security issues

Do not report security vulnerabilities in public issues. Contact the maintainers
privately so the issue can be handled responsibly.

## License

By contributing, you agree that your contribution will be licensed under the MIT
license used by this project.
