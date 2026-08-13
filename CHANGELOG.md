# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [v2.0.0] - 2026-08-13

### Added
- Reset of the opcode cache after a successful core update, for setups where
  updates run through WP-CLI and `opcache.validate_timestamps` is disabled

### Changed
- **Breaking:** minimum supported PHP version raised from 7.0 to 7.2
- PHPCS checks up to PHP 8.5
- Test matrix extended to PHP 7.2, 8.3, 8.4 and 8.5

## [v1.0.0] - 2023-02-08

### Added
- Allowed plugins in Composer config

### Changed
- PHPCS checks up to 8.2
- Strict validation of Composer files

## [v0.2.0] - 2021-05-22

### Added
- PHPCS and coding standards definitions
- Workflow to test for coding standards
- Configuration file for Dependabot

### Changed
- Remove unneeded entries in .gitattributes

## [v0.1.1] - 2021-05-22

### Added
- Add .editorconfig

### Changed
- Fix wrong package name in README

## [v0.1.0] - 2021-05-02

- First release.
