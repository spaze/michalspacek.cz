# Cryptography Primitives used in Halite

* Symmetric-key encryption: (note: only [authenticated encryption](https://tonyarcieri.com/all-the-crypto-code-youve-ever-written-is-probably-broken) is available through Halite)
  * [**XChaCha20**](https://libsodium.gitbook.io/doc/advanced/stream_ciphers/xchacha20) then BLAKE2b-MAC
  * Previously, [**XSalsa20**](https://paragonie.com/book/pecl-libsodium/read/08-advanced.md#crypto-stream) then BLAKE2b-MAC
* Symmetric-key authentication: **[BLAKE2b](https://download.libsodium.org/doc/hashing/generic_hashing.html#singlepart-example-with-a-key)** (keyed)
* Asymmetric-key encryption: [**X25519**](https://paragonie.com/book/pecl-libsodium/read/08-advanced.md#crypto-scalarmult)
  then [**HKDF-BLAKE2b**](Classes/Util.md#raw_keyed_hash), followed by symmetric-key authenticated encryption
* Asymmetric-key digital signatures: [**Ed25519**](https://paragonie.com/book/pecl-libsodium/read/05-publickey-crypto.md#crypto-sign)
* Checksums: [**BLAKE2b**](https://paragonie.com/book/pecl-libsodium/read/06-hashing.md#crypto-generichash)
* Key splitting: [**HKDF-BLAKE2b**](Classes/Util.md#splitkeys)
* Password-Based Key Derivation: [**Argon2**](https://paragonie.com/book/pecl-libsodium/read/07-password-hashing.md#crypto-pwhash-str)

In all cases, we follow an Encrypt-then-MAC construction, thus avoiding the [cryptographic doom principle](https://moxie.org/2011/12/13/the-cryptographic-doom-principle.html).

As a consequence of our use of a keyed BLAKE2b hash as a MAC, instead of GCM/Poly1305,
Halite ciphertexts are [**message committing**](https://eprint.iacr.org/2020/1456) which makes ciphertexts random key robust.  
