<?php
declare(strict_types = 1);

namespace Spaze\Encryption;

use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\HiddenString\HiddenString;
use SensitiveParameter;
use SodiumException;
use Spaze\Encryption\Exceptions\ActiveKeyIdNotFoundException;
use Spaze\Encryption\Exceptions\FormatMarkerMismatchException;
use Spaze\Encryption\Exceptions\InvalidCipherTextFormatException;
use Spaze\Encryption\Exceptions\InvalidKeyEncodingException;
use Spaze\Encryption\Exceptions\InvalidKeyIdException;
use Spaze\Encryption\Exceptions\InvalidKeyLengthException;
use Spaze\Encryption\Exceptions\InvalidKeyPrefixException;
use Spaze\Encryption\Exceptions\InvalidKeyRoleException;
use Spaze\Encryption\Exceptions\InvalidNumberOfComponentsException;
use Spaze\Encryption\Exceptions\KeyPairMismatchException;
use Spaze\Encryption\Exceptions\MissingKeyPrefixException;
use Spaze\Encryption\Exceptions\MissingSecretKeyException;
use Spaze\Encryption\Exceptions\UnknownEncryptionKeyIdException;
use Spaze\Encryption\Exceptions\UnknownFormatMarkerException;
use Spaze\Encryption\Format\AsymmetricKeyRole;
use Spaze\Encryption\Format\FormatMarker;
use Spaze\Encryption\Format\KeyEnvelope;
use TypeError;
use function array_keys;

class AnonymousPublicKeyEncryption
{

	use KeyEnvelope;


	/** @var array<string, HiddenString> */
	private array $secretKeys = [];

	/** @var array<string, HiddenString> */
	private array $publicKeys = [];


	/**
	 * Encryption to a public key: anyone with the public key can encrypt, only whoever holds the matching
	 * secret key can decrypt, and the encrypted value does not say who created it.
	 * A deployment that only encrypts needs only the public keys.
	 *
	 * @param array<array-key, string> $secretKeys key id => secret key; needed only where the data is decrypted, may be empty
	 * @param array<array-key, string> $publicKeys key id => public key; a key id missing here is derived from the secret key
	 * @throws ActiveKeyIdNotFoundException
	 * @throws InvalidKeyEncodingException
	 * @throws InvalidKeyIdException
	 * @throws InvalidKeyLengthException
	 * @throws InvalidKeyPrefixException
	 * @throws InvalidKeyRoleException
	 * @throws KeyPairMismatchException
	 * @throws MissingKeyPrefixException
	 * @throws SodiumException
	 */
	public function __construct(
		#[SensitiveParameter] array $secretKeys,
		#[SensitiveParameter] array $publicKeys,
		private string $activeKeyId,
		private string $keyPrefix,
	) {
		$this->secretKeys = $this->decodeKeys($secretKeys, $this->keyPrefix, SODIUM_CRYPTO_BOX_SECRETKEYBYTES, AsymmetricKeyRole::Secret);
		$this->publicKeys = $this->decodeKeys($publicKeys, $this->keyPrefix, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, AsymmetricKeyRole::Public);
		foreach (array_keys($secretKeys) as $id) {
			$id = (string)$id;
			$derivedPublicKey = sodium_crypto_box_publickey_from_secretkey($this->secretKeys[$id]->getString());
			if (isset($this->publicKeys[$id])) {
				// A swapped or stale pair should fail now, not weeks later on the first decrypt
				if (!hash_equals($this->publicKeys[$id]->getString(), $derivedPublicKey)) {
					throw new KeyPairMismatchException($id);
				}
			} else {
				$this->publicKeys[$id] = new HiddenString($derivedPublicKey);
			}
		}
		if (!isset($this->publicKeys[$this->activeKeyId])) {
			throw new ActiveKeyIdNotFoundException($this->activeKeyId);
		}
	}


	/**
	 * @throws InvalidKey
	 * @throws InvalidType
	 * @throws SodiumException
	 * @throws TypeError
	 */
	public function encrypt(#[SensitiveParameter] string $data): string
	{
		// The constructor guarantees the active key id has a public key, configured or derived
		$cipherText = Crypto::seal(new HiddenString($data), new EncryptionPublicKey($this->publicKeys[$this->activeKeyId]));
		return $this->formatKeyCipherText($this->activeKeyId, FormatMarker::AnonymousPublicKeyV1, $cipherText);
	}


	/**
	 * Halite reports any well-formed value it cannot decrypt as InvalidKey, a wrong key and corrupted data
	 * are indistinguishable here; only a value that is not even valid base64 throws InvalidMessage instead.
	 *
	 * @throws FormatMarkerMismatchException
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidType
	 * @throws MissingSecretKeyException
	 * @throws SodiumException
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 * @throws UnknownFormatMarkerException
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 */
	public function decrypt(string $data): string
	{
		[$keyId, $marker, $cipherText] = $this->parseKeyCipherText($data);
		$this->checkFormatMarker($marker, FormatMarker::AnonymousPublicKeyV1);
		return Crypto::unseal($cipherText, $this->getSecretKey($keyId))->getString();
	}


	/**
	 * Checks if the given data should be re-encrypted with the currently active key:
	 * either they are encrypted with an inactive key, or they are stored in the older format
	 * without the marker, and re-encrypting them adds it.
	 *
	 * @throws FormatMarkerMismatchException
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 * @throws UnknownFormatMarkerException
	 */
	public function needsReEncrypt(string $data): bool
	{
		return $this->needsReEncryptMarked($data, $this->activeKeyId, FormatMarker::AnonymousPublicKeyV1);
	}


	/**
	 * @throws InvalidKey
	 * @throws MissingSecretKeyException
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 */
	private function getSecretKey(string $keyId): EncryptionSecretKey
	{
		if (isset($this->secretKeys[$keyId])) {
			return new EncryptionSecretKey($this->secretKeys[$keyId]);
		}
		if (isset($this->publicKeys[$keyId])) {
			// The key id is known, this deployment just can't decrypt, only encrypt
			throw new MissingSecretKeyException($keyId);
		}
		throw new UnknownEncryptionKeyIdException($keyId);
	}

}
