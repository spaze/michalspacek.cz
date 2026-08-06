# Various encryption helpers

Various PHP encryption helpers, uses [`paragonie/halite`](https://github.com/paragonie/halite) (which uses [Sodium](https://php.net/sodium)) for cryptography. Supports key rotation.

[![PHP Tests](https://github.com/spaze/encryption/actions/workflows/php.yml/badge.svg)](https://github.com/spaze/encryption/actions/workflows/php.yml)

## Installation
```bash
composer require spaze/encryption
```

## Usage
This library provides symmetric encryption, where one key both encrypts and decrypts, [encryption between two parties](#encryption-between-two-parties), where each side holds its own secret key and the other side's public key, and [encryption to a public key](#encryption-to-a-public-key), where the key used to encrypt cannot decrypt the data. It uses [Halite](https://github.com/paragonie/halite), which relies on [libsodium](https://pecl.php.net/package/libsodium) for all of its underlying cryptography operations.
Read the [Halite documentation](https://github.com/paragonie/halite/tree/master/doc) for more details, including the [cryptography primitives](https://github.com/paragonie/halite/blob/master/doc/Primitives.md) it uses.
At the moment, signatures are not supported by this library.

The library is framework-agnostic, with minimal dependencies.

### Create the object using the constructor
```php
Spaze\Encryption\SymmetricKeyEncryption::__construct(array $keys, string $activeKeyId, string $keyPrefix)
```
#### `array $keys`
An array of encryption keys, a _key id_ (will be part of the encrypted string) as the array key, the prefixed _key_ (`prefix` + `_` + `[0-9a-fA-F]{64}`) as the value.
Generate your own encryption keys with for example `sodium_bin2hex(random_bytes(32))`.
The constructor validates each key: the prefix must match, the key material must be valid hex, and it must decode to exactly 32 bytes (64 hexadecimal characters). The key id must be non-empty and must not contain `$`, because the id becomes part of the encrypted output format. A misconfigured key throws an exception at construction time, not on first use.

#### `string $activeKeyId`
A `key id` of a key that should be used for encryption. Decryption will always use a key that's specified in the encryption output.
The id must exist as a key in the `$keys` array, otherwise the constructor throws `ActiveKeyIdNotFoundException` — a typo in the active key id fails at construction time, not on the first `encrypt()` call after a deploy.

#### `string $keyPrefix`
A prefix that the encryption key uses for better identification, useful when you've found some leaked credentials for example.
Usually this is an abbreviation or an initialism of the intended usage, for example `adek`: *a*ddress *d*ata *e*ncryption *k*ey.

Example:
```php
$keys = [
    'key1' => 'adek_79e0[...]8a8d',
    'key2' => 'adek_d22c[...]cfa3',
];
$activeKeyId = 'key2';
$keyPrefix = 'adek';
$encryption = new Spaze\Encryption\SymmetricKeyEncryption($keys, $activeKeyId, $keyPrefix);
```

### Encrypt
```php
Spaze\Encryption\SymmetricKeyEncryption::encrypt(string $data): string
```
The output will be formatted as `$<keyId>$SymV1$<base64 ciphertext>`, for example `$key2$SymV1$MUI...`, where `<keyId>` (`key2`) is the active key id set in the constructor. Store the whole value, don't parse it.

The marker between the key id and the encrypted part says what created the value: feeding an `encryptWithAd()` value (marked `SymAdV1`) to `decrypt()`, or a value from a different class to this one, fails with an exception that says what to call instead. The markers can never change; a future format change would introduce new marker values, so the digit works as a format version.

The key id and the marker are protected against tampering: both go into what decryption verifies, so changing either of them in a stored value makes decryption fail. (The verified value is `{"keyId":"<base64>","marker":"<the marker from the stored value>"}` — so `SymV1` or `SymAdV1` — with an `"additionalData":"<base64>"` member added by `encryptWithAd()`; Base64 being the URL-safe kind with padding. This only matters if you ever need to decrypt the data with Halite directly, without this library.)

Values in the older format without the marker, `$<keyId>$<base64 ciphertext>`, written by previous versions, still decrypt. Their key id is not protected against tampering though: changing it just makes decryption try a different key, and fail because the key is different — so for them, never configure the same key under two different ids. Versions without marker support cannot read the marked values, so when multiple deployments share the data, upgrade all of them before writing anything new.

This method does not bind the ciphertext to a context of your own. Use `encryptWithAd()` if you want that.

Example:
```php
$encrypted = $encryption->encrypt($addressData);
```

### Encrypt with Additional Authenticated Data (AAD)
```php
Spaze\Encryption\SymmetricKeyEncryption::encryptWithAd(string $data, string $additionalData): string
```
Additional Authenticated Data (AAD) cryptographically binds a ciphertext to a context (like a row id, column name, or tenant id). The additional data (the context) is **not encrypted**, and thus it must not be a secret. This prevents attackers or buggy scripts from copying a valid ciphertext from one place and pasting it into another.

The `$additionalData` must be non-empty and exactly the same on both encrypt and decrypt, otherwise decryption will fail.

Example:
```php
$encrypted = $encryption->encryptWithAd($addressData, $tenantId);
```

### Decrypt
```php
Spaze\Encryption\SymmetricKeyEncryption::decrypt(string $data): string
```
Use it to decrypt data previously encrypted with `encrypt()`.

Example:
```php
$decrypted = $encryption->decrypt($encrypted);
```

### Decrypt with Additional Authenticated Data (AAD)
```php
Spaze\Encryption\SymmetricKeyEncryption::decryptWithAd(string $data, string $additionalData): string
```
Use it to decrypt data previously encrypted with `encryptWithAd()`.

Example:
```php
$decrypted = $encryption->decryptWithAd($encrypted, $tenantId);
```

### Key rotation
You can always add a new encryption key, set it as an active key and from that moment, the data will be encrypted with the new key.
Unless you remove the old key, it will be possible to decrypt data encrypted with it.
You can then take all the data encrypted with the old key and re-encrypt them just to change the key which was used to encrypt them.
Once done you can delete the old key.

You can use `needsReEncrypt($ciphertext): bool` to see if the data is encrypted with an inactive key and thus should be re-encrypted with the currently active one. It also returns true for values stored in the [older format](#encrypt) without the marker, so the same re-encryption sweep migrates them to the marked format.

Values created by `encryptWithAd()` have to be re-encrypted with `decryptWithAd()` and `encryptWithAd()`, using the same additional data the row was encrypted with. Marked values say which method created them, values in the older format don't, so a sweep over old data has to know on its own which rows are context-bound and what their context is.

When rotating, always generate a fresh key for the new key id. In values written in the older format the key id is not protected against tampering (see [Encrypt](#encrypt)), so two different key ids must never point to the same key.

## Encryption between two parties

`Spaze\Encryption\AuthenticatedPublicKeyEncryption` encrypts data exchanged between two parties. Each side configures its own secret key and the other side's public key under the same key id. Both sides can encrypt and decrypt, and decryption only succeeds when the data was created with one of the two configured keys, so a successful decryption also proves the data came from the other party, or from us.

Because both sides can decrypt, this class does not hide the data from whoever can encrypt it. If both sides would be configured with the same keys anyway, use `SymmetricKeyEncryption` instead.

### Create the object using the constructor
```php
Spaze\Encryption\AuthenticatedPublicKeyEncryption::__construct(array $secretKeys, array $publicKeys, string $activeKeyId, string $keyPrefix)
```

#### `array $secretKeys`
An array of our own secret keys, a _key id_ (will be part of the encrypted string) as the array key, the prefixed _key_ as the value.

#### `array $publicKeys`
An array of the other party's public keys, one for every key id in `$secretKeys`. Every key id needs both keys, a key id with only one of them throws `IncompleteKeyPairException`.

The values are validated like the symmetric keys (the prefix must match, the key must be valid hex and decode to exactly 32 bytes, the key id must be non-empty and must not contain `$`), with one addition: the value can carry a tag between the prefix and the key that says which kind of key it is, `adek_secret_79e0[...]8a8d` or `adek_public_d22c[...]cfa3`. The tag is optional, plain `adek_79e0[...]8a8d` values are accepted too, so existing configurations can be reused unchanged. Tagged keys are recommended though: a secret key pasted where a public key belongs (or the other way around) then throws `InvalidKeyRoleException` when the object is created, instead of producing encrypted data that nobody will ever be able to decrypt. And when you find a leaked string somewhere, the tag tells you how bad it is: `adek_secret_` means rotate the keys now, a public key is not a secret.

#### `string $activeKeyId` and `string $keyPrefix`
Same meaning and validation as in `SymmetricKeyEncryption` above.

Example:
```php
$secretKeys = [
    'key1' => 'adek_secret_79e0[...]8a8d',
];
$publicKeys = [
    'key1' => 'adek_public_d22c[...]cfa3',
];
$encryption = new Spaze\Encryption\AuthenticatedPublicKeyEncryption($secretKeys, $publicKeys, 'key1', 'adek');
```

### Generating a key pair
Each party generates their own pair, keeps the secret key to themselves and gives the public key to the other party:
```php
$keyPair = sodium_crypto_box_keypair();
$secretKey = 'adek_secret_' . sodium_bin2hex(sodium_crypto_box_secretkey($keyPair));
$publicKey = 'adek_public_' . sodium_bin2hex(sodium_crypto_box_publickey($keyPair));
```

### Encrypt & decrypt
The methods are the same as in `SymmetricKeyEncryption`: `encrypt()`, `decrypt()`, `encryptWithAd()` and `decryptWithAd()` for [context binding](#encrypt-with-additional-authenticated-data-aad), and `needsReEncrypt()` for [key rotation](#key-rotation).

The output looks like `$<keyId>$AuthV1$<base64 ciphertext>`, or `$<keyId>$AuthAdV1$<...>` when created by `encryptWithAd()`. The marker between the key id and the encrypted part says what created the value: feeding an `encryptWithAd()` value to `decrypt()`, or a value from a different class to this one, fails with an exception that says what to call instead. The markers can never change; a future format change would introduce new marker values, so the digit works as a format version.

Like in `SymmetricKeyEncryption`, the key id and the marker are protected against tampering: both go into what decryption verifies, so changing either of them in a stored value makes decryption fail. (The verified value is built [the same way as in `SymmetricKeyEncryption`](#encrypt), with `AuthV1` or `AuthAdV1` as the marker.)

Values in the older format without the marker — for example written by a previous library that used the same format — still decrypt, though their key id is not protected against tampering, and `needsReEncrypt()` returns true for them, so a usual re-encryption sweep migrates them to the marked format.

One thing deserves a special mention: a configuration with the two keys accidentally swapped can still encrypt and decrypt its own data just fine, only the data from the other party will fail to decrypt. When setting up, always verify by decrypting a value the other party encrypted, not one you encrypted yourself.

When either side replaces their keys, configure the new pair under a new key id on both sides and rotate the same way as with symmetric keys.

## Encryption to a public key

`Spaze\Encryption\AnonymousPublicKeyEncryption` encrypts data to a public key: anyone who has the public key can encrypt, only whoever holds the matching secret key can decrypt, and the encrypted value does not say who created it.

The point is the split: a server that only stores the data can be configured with just the public keys and cannot read anything back, not even the values it has just encrypted itself. Decryption then happens elsewhere, in a back office application or a worker that holds the secret keys. If every deployment would hold the secret keys anyway, use `SymmetricKeyEncryption` instead, and if the reader also needs to know who created the data, use `AuthenticatedPublicKeyEncryption`.

### Create the object using the constructor
```php
Spaze\Encryption\AnonymousPublicKeyEncryption::__construct(array $secretKeys, array $publicKeys, string $activeKeyId, string $keyPrefix)
```

#### `array $secretKeys`
The secret keys, needed only where the data is decrypted. Encrypt-only deployments pass an empty array.

#### `array $publicKeys`
The public keys. A key id missing here is derived from the secret key with the same id, so a decrypting deployment can configure just the secret keys. When a key id has both values configured, the constructor verifies they belong together and throws `KeyPairMismatchException` when they don't, so a swapped or stale pair fails at construction and not weeks later on the first decrypt.

The values are validated like in the other classes, including the optional `secret`/`public` tag (see [encryption between two parties](#encryption-between-two-parties)), and the pair is generated [the same way](#generating-a-key-pair). The active key id must have a public key, configured or derived.

Example, the encrypting side:
```php
$encryption = new Spaze\Encryption\AnonymousPublicKeyEncryption([], ['key1' => 'adek_public_d22c[...]cfa3'], 'key1', 'adek');
$encrypted = $encryption->encrypt($addressData); // works
$decrypted = $encryption->decrypt($encrypted); // throws MissingSecretKeyException
```
The decrypting side:
```php
$encryption = new Spaze\Encryption\AnonymousPublicKeyEncryption(['key1' => 'adek_secret_79e0[...]8a8d'], [], 'key1', 'adek');
```

### Encrypt & decrypt
`encrypt()`, `decrypt()` and `needsReEncrypt()` work like in the other two classes, but there are no `encryptWithAd()`/`decryptWithAd()` methods, this flavor cannot bind the encrypted value to a context.

The output looks like `$<keyId>$AnonV1$<base64 ciphertext>`, where `AnonV1` is the marker saying what created the value — a value from a different class fails with an exception that names its creator. Values in the older format without the marker still decrypt, and `needsReEncrypt()` returns true for them, so a re-encryption sweep migrates them to the marked format. Unlike in the other two classes, the key id and the marker are not protected against tampering here — a sealed value has no place to verify them, so changing the key id just makes decryption try a different key, and never configure the same key under two different ids.

Trying to decrypt a key id that only has a public key configured throws `MissingSecretKeyException`, which usually means the code runs on an encrypt-only deployment. Re-encryption after a key rotation therefore has to run where the secret keys live: configure the old secret key and the new public key (or the new pair), and the data encrypted with the old key can be decrypted and re-encrypted with the new one.

### When decryption fails
Values with a marker say what created them: every class refuses another class's marked values with an exception that names the creator, so mixed-up values are easy to diagnose. The detective work below is only needed for values in the older format without the marker.

Halite reports any well-formed unmarked value that `AnonymousPublicKeyEncryption` cannot decrypt as `InvalidKey: Incorrect secret key for this sealed message`: a wrong key, corrupted data, and data that was actually created by `SymmetricKeyEncryption` or `AuthenticatedPublicKeyEncryption` all look the same. Only a value that is not even valid base64 gets a different error, `InvalidMessage: Invalid character encoding`. If you see the wrong-key error on data that should be fine, check which class created the value: `SymmetricKeyEncryption` and `AuthenticatedPublicKeyEncryption` fail with `InvalidMessage` when fed each other's data or data created by `AnonymousPublicKeyEncryption`. Their encrypted part also always starts with `MUI` — the beginning of a header Halite adds to the output of those two classes, with the next characters changing with the Halite version — while the encrypted part made by `AnonymousPublicKeyEncryption` has no header and looks random. For unmarked values the key id is the only reliable way to tell them apart, so don't reuse a key id across classes.

## Usage in Nette framework

Although it can be used anywhere, this library doesn't depend on anything from the Nette Framework.

### Define encryption keys

Add this (or similar) to your `config.local.neon` parameters section (DO NOT COMMIT THIS TO REPOSITORY):
```
parameters:
    encryption:
        keys:
            passwordHash:
                prod1: "phek_abadcafec15c..." # prefix _ [0-9a-fA-F]{64}
            email:
                prod1: "eek_cafebabe25da..." # prefix _ [0-9a-fA-F]{64}
        activeKeyIds:
            passwordHash: prod1
            email: prod1
        prefixes:
            passwordHash: phek # password hash encryption key
            email: eek # email encryption key
```
Note that Nette compiles parameter values into the generated DI container file in the temp directory, so the keys will also be present in plaintext in the compiled container in `temp/cache`.
That directory tends to leak into places nobody thinks about: backups, deploy artifacts, rsync copies, debug tarballs sent to hosting support.
Either treat the temp directory accordingly and exclude it from backups and artifacts, or use [dynamic parameters](https://doc.nette.org/en/application/bootstrapping#toc-dynamic-parameters) or environment variables so the key values are not baked into the compiled container.

Exception logs are one of those places too. When a key is misconfigured, the constructors of these classes throw an exception while the container is creating the service, and Tracy logs that exception as an HTML file that includes the code around every line in the stack trace, the container line that passes the keys among them. Neither `#[SensitiveParameter]` nor `zend.exception_ignore_args` prevents that, both hide the values passed to a function, not the code printed around them.

Anything that keeps the keys out of the generated container keeps them out of such a log as well. Besides the options above, you can also pass them as a runtime call, because Nette compiles a `@service::method()` argument into a call instead of a literal:
```neon
services:
    encryptionKeys: Your\EncryptionKeys(%encryption.keyFile%)
    emailEncryption: Spaze\Encryption\SymmetricKeyEncryption(@encryptionKeys::get('email'), %encryption.activeKeyIds.email%, %encryption.prefixes.email%)
```
A file the service reads is a good fit if you want to keep the keys in a file: make it a PHP file that returns an array and OPcache will keep it compiled, so there's no parsing on each request. Whichever way you go, grep the generated container for a key prefix to confirm the keys are no longer in it.

YOU HAVE TO GENERATE YOUR OWN KEYS. You can use for example
```php
sodium_bin2hex(random_bytes(32))
```
to generate a key, then add the prefix. You can have multiple keys in each group (here we see two groups: `password` and `email`), meaning you will be able to decrypt data encrypted with these keys. Data will always be encrypted with what's defined in `activeKeyIds` section.

The configuration is an example one, you don't need to use groups, or even the config key names (like `activeKeyIds`), the only place where these will be used is when you define the service, or services. 

### Services
Then define services for each key group (feel free to commit this):
```
services:
    emailEncryption: \Spaze\Encryption\SymmetricKeyEncryption(%encryption.keys.email%, %encryption.activeKeyIds.email%, %encryption.prefixes.email%)
    passwordHashEncryption: \Spaze\Encryption\SymmetricKeyEncryption(%encryption.keys.passwordHash%, %encryption.activeKeyIds.passwordHash%, %encryption.prefixes.passwordHash%)
```

The two public-key classes take two key arrays, so their groups need two lists in the parameters:
```
services:
    invoiceEncryption: \Spaze\Encryption\AnonymousPublicKeyEncryption(%encryption.secretKeys.invoice%, %encryption.publicKeys.invoice%, %encryption.activeKeyIds.invoice%, %encryption.prefixes.invoice%)
```
On a deployment that only encrypts, define the secret keys list as empty (`secretKeys: {invoice: []}`) and keep the secret keys out of its configuration entirely.

Use the services in this class which needs to encrypt and decrypt email addresses for whatever reason:
```php
use Spaze\Encryption\SymmetricKeyEncryption;

class Something
{

    public function __construct(
        private SymmetricKeyEncryption $emailEncryption,
    ) {
        // ...
    }

    public function doSomething()
    {
        // ...
        $encryptedEmail = $this->emailEncryption->encrypt($email);
        // ...
    }


    public function doSomethingElse()
    {
        // ...
        $decryptedEmail = $this->emailEncryption->decrypt($email);
        // ...
    }

}
```

Pass the properly configured encryption service to the class:
```
services:
    something: Something(emailEncryption: @emailEncryption)
```

## Running tests

If you want to contribute (awesome, thanks!), you should add/run tests for your contributions.
First install dev dependencies by running `composer install`, then run tests with `composer test`, see `scripts` in `composer.json`. Tests are also run on GitHub with Actions on each push.

You can fix coding style issues automatically by running `composer cs-fix`.
