# Radicle Surf

## Introduction

Radicle Surf is a light-weight command-line interface for interacting with Radicle's default Docker development environment inspired by [Laravel Sail](https://github.com/laravel/sail). Surf provides a great starting point for building a Radicle application using PHP, MySQL, and Redis without requiring prior Docker experience.

At its heart, Surf is the `docker-compose.yml` file and the `surf` script that is stored at the root of your project. The `surf` script provides a CLI with convenient methods for interacting with the Docker containers defined by the `docker-compose.yml` file.

Radicle Surf is supported on macOS, Linux, and Windows (via [WSL2](https://docs.microsoft.com/en-us/windows/wsl/about)).

## Installation & Setup

You may simply install Surf using the Composer package manager. Of course, these steps assume that your existing local development environment allows you to install Composer dependencies:

```bash
composer require thecoati/surf --dev
```

After Surf has been installed, you may run the `surf install`. This command will publish Surf's `docker-compose.yml` file to the root of your application:

```bash
./vendor/bin/surf install
```

Finally, you may start Surf. To continue learning how to use Surf, please continue reading the remainder of this documentation:

```bash
./vendor/bin/sail up
```
