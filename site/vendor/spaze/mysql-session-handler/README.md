# MySQL Session handler

Custom PHP session handler for [Nette Framework](http://nette.org/) that uses MySQL database for storage.

## Requirements

- [nette/database](https://github.com/nette/database) 2.4+
- PHP 7.2+

## Installation

Preferred way to install spaze/mysql-session-handler is by using [Composer](http://getcomposer.org/):

```sh
$ composer require spaze/mysql-session-handler
```

## Setup

After installation:

1) Create the table sessions using SQL in [sql/create.sql](sql/create.sql).

2) Register an extension in config.neon:

```neon
	extensions:
		sessionHandler: Spaze\Session\DI\MysqlSessionHandlerExtension
```

## Features

- For security reasons, Session ID is stored in the database as an SHA-256 hash.
- Multi-Master Replication friendly (tested in Master-Master row-based replication setup).

## Credits

This is heavily based on [MySQL Session handler](https://github.com/pematon/mysql-session-handler) by [Pematon](https://github.com/orgs/pematon/people) ([Marián Černý](https://github.com/mariancerny) & [Peter Knut](https://github.com/peterpp)), thanks!
