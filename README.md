# Radicle Surf

Radicle Surf is [Laravel Sail](https://github.com/laravel/sail) modified to be compatible with [Radicle](https://roots.io/radicle/). 

> **Note** \
> Radicle Surf is currently still under development. \
> Currently only the **MySQL** service has been added. 

## Table of Contents

- [Introduction](#introduction)
- [Installation & Setup](#installation--setup)
    - [Configuring A Shell Alias](#configuring-a-shell-alias)

## Introduction

Radicle Surf is a light-weight command-line interface for setting up a Radicle Docker development environment based on [Laravel Sail](https://github.com/laravel/sail). Surf provides a great starting point for building a Radicle application using PHP, MySQL, and Redis without requiring prior Docker experience.

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

Finally, you may start Surf.

```bash
./vendor/bin/surf up
```

### Configuring A Shell Alias

By default, Surf commands are invoked using the `vendor/bin/surf` script:

```shell
./vendor/bin/surf up
```

However, instead of repeatedly typing `vendor/bin/surf` to execute Surf commands, you may wish to configure a shell alias that allows you to execute Surf's commands more easily:

```shell
alias surf='[ -f surf ] && sh surf || sh vendor/bin/surf'
```

To make sure this is always available, you may add this to your shell configuration file in your home directory, such as `~/.zshrc` or `~/.bashrc`, and then restart your shell.

Once the shell alias has been configured, you may execute Surf commands by simply typing `surf`. The remainder of this documentation's examples will assume that you have configured this alias:

```bash
sail up
```
