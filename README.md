[![Coding Standards](https://github.com/benoitchantre/wp-composer-auto-updates/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/benoitchantre/wp-composer-auto-updates/actions/workflows/coding-standards.yml)

# WP Composer Auto Updates

WordPress MU-Plugin to enable maintenance and security updates when the site uses version control and `DISALLOW_FILE_MODS`.

When `DISALLOW_FILE_MODS` is not set or false, WordPress will behave as if there was no VCS: plugins and themes can be installed or updated from the dashboard.
In this scenario, `composer.lock` will get out of sync. It can be used to hand-off a project to a client.

## Installation

This package can be installed in the `mu-plugins` directory with `composer/installers`.
As WordPress only load php files inside `mu-plugins` directory, it needs to be included required by a file or autoloaded using `roots/bedrock-autoloader` or similar solutions.

Example of a `composer.json` to manage a WordPress site with Composer:

```json
{
  "require": {
    "php": ">=7.0",
    "benoitchantre/wp-composer-auto-updates": "^1.0",
    "composer/installers": "^1.0", 
    "johnpbloch/wordpress": "^5.5", 
    "roots/bedrock-autoloader": "^1.0"
  },
  "extra": {
    "wordpress-install-dir": "public/wp",
    "installer-paths": {
      "public/wp-content/mu-plugins/{$name}": [
        "type:wordpress-muplugin"
      ],
      "public/wp-content/plugins/{$name}/": [
        "type:wordpress-plugin"
      ],
      "public/wp-content/themes/{$name}/": [
        "type:wordpress-theme"
      ]
    }
  }
}
```
