# MySQL Session handler

Custom PHP session handler for [Nette Framework](http://nette.org/) that uses MySQL database for storage.

## Requirements

- [nette/database](https://github.com/nette/database) 3.2+
- [nette/di](https://github.com/nette/di) 3.2+
- PHP 8.2+

## Installation

The preferred way to install spaze/mysql-session-handler is by using [Composer](http://getcomposer.org/):

```sh
$ composer require spaze/mysql-session-handler
```

## Setup

After installation:

1) Create a table named `sessions` using SQL in [sql/create.sql](sql/create.sql). The name of the table can be changed in the configuration using the `tableName` key, like this:
```neon
sessionHandler:
    tableName: sessions_table
```

2) Register the extension in your configuration file (e.g. `config.neon`):

```neon
	extensions:
		sessionHandler: Spaze\Session\DI\MysqlSessionHandlerExtension
```

## Garbage collection

Expired sessions are deleted only when the handler's `gc()` method runs. It deletes rows with a `timestamp` older than the lifetime it's called with. When PHP calls it, the lifetime is `session.gc_maxlifetime`.

The timestamp is updated when the session data changes. When the data doesn't change, the timestamp is updated at most once per `unchangedUpdateDelay` seconds, 300 by default. Keep the lifetime well above that.

On MySQL servers with `@@server_id` from 2 to 9, `gc()` keeps rows longer, to avoid conflicts in master-master replication. For each server ID above 1, it adds a day or a tenth of the lifetime, whichever is more.

Until a row is deleted, the expired session is still accepted, even with `session.use_strict_mode` enabled. PHP's own `files` session handler works the same way.

PHP calls `gc()` when a session is started, with a probability of `session.gc_probability`/`session.gc_divisor`. It also calls it every time `session_gc()` is called while a session is active. With a low probability and little traffic, expired sessions can stay usable for a long time.

Some Linux distributions set `session.gc_probability` to 0 and delete expired sessions with their own cron job instead. Such a job deletes only the files of the `files` session handler, not the rows in your sessions table. In that case, call the handler's `gc()` with your session lifetime from your own cron job.

The `timestamp` column has an index, so that `gc()` doesn't have to read the whole table. With the default transaction isolation level, `gc()` keeps the rows it reads locked until it's done. Without the index, it reads and locks all rows, and other requests have to wait before they can save their sessions. If your table doesn't have the index, add it:

```sql
ALTER TABLE `sessions` ADD KEY `timestamp` (`timestamp`);
```

## Features

- For security reasons, the session id is stored in the database as an SHA-256 hash.
- Supports encrypted session storage via [spaze/encryption](https://github.com/spaze/encryption) which uses [paragonie/halite](https://github.com/paragonie/halite) which uses [Sodium](https://php.net/sodium).
- Events that allow you, for example, to add additional columns to the session storage table.
- Multi-master replication-friendly (tested in master-master row-based replication setup).

## Encrypted session storage

Follow the guide at [spaze/encryption](https://github.com/spaze/encryption#usage-in-nette-framework) to create and configure a new encryption key.

Define a new service:
```
sessionEncryption: \Spaze\Encryption\SymmetricKeyEncryption(%encryption.keys.session%, %encryption.activeKeyIds.session%, %encryption.prefixes.session%)
```

Add the new encryption service to the session handler:
```
sessionHandler:
    encryptionService: @sessionEncryption
```

Migration from unencrypted to encrypted session storage is not (yet?) supported.

## Events

### `onBeforeDataWrite`
The event occurs before session data is written to the session table, both for a new session (when a new row is inserted) and for an existing session (when a row is updated), even if there is no change in the session data.

## Additional columns

You can add a new column to the session table by calling `setAdditionalData()` in the event handler:
```
setAdditionalData(string $key, $value): void
```
Use it to store, for example, the user id the session belongs to. See for example [this code](https://github.com/spaze/michalspacek.cz/blob/fbd438e8f4c1da658a88bc8c3bf5af59fcd063e6/app/src/Application/WebApplication.php#L42-L50) which uses the `Nette\Security\User::onLoggedIn` handler to do that.

## Locking

Requests using the same session wait for each other, the session is locked with a MySQL named lock from when it's started until it's closed. If the lock is not acquired within the timeout, 5 seconds by default, `Spaze\Session\Exceptions\SessionLockException` is thrown, so that the request doesn't overwrite changes made by the request holding the lock. Close the session early in long-running requests, with `session_write_close()` or `Nette\Http\Session::close()`, or change the timeout:
```neon
sessionHandler:
    lockTimeout: 10
```
A timeout of 0 doesn't wait at all. On MySQL, a negative timeout waits indefinitely, like PHP's own `files` session handler, but MariaDB doesn't support negative timeouts.

If the session is started automatically, for example by your framework when a request has a session cookie, the request holds the lock even if it doesn't use the session. The exception can then come from the framework's startup code, before your application's own error page can handle it.

## Credits

This is heavily based on [MySQL Session handler](https://github.com/pematon/mysql-session-handler) by [Pematon](https://github.com/orgs/pematon/people) ([Marián Černý](https://github.com/mariancerny) & [Peter Knut](https://github.com/peterpp)), thanks!
