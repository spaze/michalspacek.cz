# MySQL Session handler

Custom PHP session handler for [Nette Framework](http://nette.org/) that uses MySQL database for storage.

## Requirements

- [nette/database](https://github.com/nette/database) 3.2+
- [nette/di](https://github.com/nette/di) 3.2+
- PHP 8.2+

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
- Supports encrypted session storage via [spaze/encryption](https://github.com/spaze/encryption) which uses [paragonie/halite](https://github.com/paragonie/halite) which uses [Sodium](https://php.net/sodium).
- Events that allow you to add additional columns to the session storage table for example.
- Multi-Master Replication friendly (tested in Master-Master row-based replication setup).

## Encrypted session storage

Follow the guide at [spaze/encryption](https://github.com/spaze/encryption#usage-in-nette-framework) to define a new encryption key.

Define a new service:
```
sessionEncryption: \Spaze\Encryption\Symmetric\StaticKey('session', %encryption.keys%, %encryption.activeKeyIds%)
```

Add the new encryption service to the session handler:
```
sessionHandler:
    encryptionService: @sessionEncryption
```

Migration from unecrypted to encrypted session storage is not (yet?) supported.

## Events

### `onBeforeDataWrite`
The event occurs before session data is written to the session table, both for a new session (when a new row is inserted) or an existing session (a row is updated), even if there's no change in the session data.

## Additional columns

You can add a new column to the session table by calling `setAdditionalData()` in the event handler:
```
setAdditionalData(string $key, $value): void
```
Use it to store for example user id to which the session belongs to. See for example [this code](https://github.com/spaze/michalspacek.cz/blob/fbd438e8f4c1da658a88bc8c3bf5af59fcd063e6/app/src/Application/WebApplication.php#L42-L50) that uses the `Nette\Security\User::onLoggedIn` handler to do that.

## Credits

This is heavily based on [MySQL Session handler](https://github.com/pematon/mysql-session-handler) by [Pematon](https://github.com/orgs/pematon/people) ([Marián Černý](https://github.com/mariancerny) & [Peter Knut](https://github.com/peterpp)), thanks!
