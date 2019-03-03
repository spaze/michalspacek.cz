# Various encryption helpers

Various encryption helpers, uses [`paragonie/halite`](https://github.com/paragonie/halite) (which uses [Sodium](https://php.net/sodium)) for cryptography. Support key rotation.

## Usage in Nette framework

### Define encryption keys

Add this to your `config.local.neon` parameters section (DO NOT COMMIT THIS TO REPOSITORY):
```
encryption:
    keys:
        passwordHash:
            prod1: "\xab\xad\xca\xfe\xc1\x5c\x..." # \x[0-9A-F]{32}
        email:
            prod1: "\xca\xfe\xba\xbe\x25\xda\x..." # \x[0-9A-F]{32}
    activeKeyIds:
        passwordHash: prod1
        email: prod1
```
YOU HAVE TO GENERATE YOUR OWN KEYS. You can use for example
```php
preg_replace('/(..)/', '\x$1', bin2hex(random_bytes(32)))
```
to generate a key. You can have multiple keys in each group (here we see two groups: `password` and `email`), meaning you will be able to decrypt data encrypted with these keys. Data will always be encrypted with what's defined in `activeKeyIds` section.

### Services
Then define services for each key group (feel free to commit this):
```
services:
    emailEncryption: \Spaze\Encryption\Symmetric\StaticKey('email', %encryption.keys%, %encryption.activeKeyIds%)
    passwordHashEncryption: \Spaze\Encryption\Symmetric\StaticKey('passwordHash', %encryption.keys%, %encryption.activeKeyIds%)
```

Use the services in this class which needs to encrypt and decrypt email addresses for whatever reason:
```php
use Spaze\Encryption\Symmetric\StaticKey as StaticKeyEncryption;

class Something
{

    /** @var StaticKeyEncryption */
    private $emailEncryption;

    public function __construct(StaticKeyEncryption $emailEncryption)
    {
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

## Key rotation
You can always add a new encryption key, set it as an active key and from that moment, the data will be encrypted with the new key. Unless you remove the old key, it will be possible to decrypt data encrypted with it. You can then take all the data encrypted with the old key and re-encrypt them just to change they key which was used to encrypt them. Once done you can delete the old key.
