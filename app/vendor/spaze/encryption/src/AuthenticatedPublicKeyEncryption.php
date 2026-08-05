<?php
declare(strict_types = 1);

namespace Spaze\Encryption;

use JsonException;
use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidDigestLength;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSignature;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Asymmetric\Crypto;
use ParagonIE\Halite\Asymmetric\EncryptionPublicKey;
use ParagonIE\Halite\Asymmetric\EncryptionSecretKey;
use ParagonIE\HiddenString\HiddenString;
use SensitiveParameter;
use SodiumException;
use Spaze\Encryption\Exceptions\ActiveKeyIdNotFoundException;
use Spaze\Encryption\Exceptions\DecryptWithAdNeedsAdditionalDataException;
use Spaze\Encryption\Exceptions\EncryptWithAdNeedsAdditionalDataException;
use Spaze\Encryption\Exceptions\FormatMarkerMismatchException;
use Spaze\Encryption\Exceptions\IncompleteKeyPairException;
use Spaze\Encryption\Exceptions\InvalidCipherTextFormatException;
use Spaze\Encryption\Exceptions\InvalidKeyEncodingException;
use Spaze\Encryption\Exceptions\InvalidKeyIdException;
use Spaze\Encryption\Exceptions\InvalidKeyLengthException;
use Spaze\Encryption\Exceptions\InvalidKeyPrefixException;
use Spaze\Encryption\Exceptions\InvalidKeyRoleException;
use Spaze\Encryption\Exceptions\InvalidNumberOfComponentsException;
use Spaze\Encryption\Exceptions\MissingKeyPrefixException;
use Spaze\Encryption\Exceptions\UnknownEncryptionKeyIdException;
use Spaze\Encryption\Exceptions\UnknownFormatMarkerException;
use Spaze\Encryption\Format\AsymmetricKeyRole;
use Spaze\Encryption\Format\FormatMarker;
use Spaze\Encryption\Format\KeyEnvelope;
use TypeError;
use function array_keys;

class AuthenticatedPublicKeyEncryption
{

	use KeyEnvelope;


	/** @var array<string, HiddenString> */
	private array $secretKeys = [];

	/** @var array<string, HiddenString> */
	private array $publicKeys = [];


	/**
	 * Encryption between two parties: each side configures its own secret key and the other side's public key under the same key id.
	 * Both sides can encrypt and decrypt, and decryption only succeeds for data created with one of the two configured keys,
	 * so it also proves the data came from the other party, or from us.
	 *
	 * @param array<array-key, string> $secretKeys key id => our secret key
	 * @param array<array-key, string> $publicKeys key id => the other party's public key
	 * @throws ActiveKeyIdNotFoundException
	 * @throws IncompleteKeyPairException
	 * @throws InvalidKeyEncodingException
	 * @throws InvalidKeyIdException
	 * @throws InvalidKeyLengthException
	 * @throws InvalidKeyPrefixException
	 * @throws InvalidKeyRoleException
	 * @throws MissingKeyPrefixException
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
			if (!isset($this->publicKeys[$id])) {
				throw new IncompleteKeyPairException((string)$id);
			}
		}
		foreach (array_keys($publicKeys) as $id) {
			if (!isset($this->secretKeys[$id])) {
				throw new IncompleteKeyPairException((string)$id);
			}
		}
		if (!isset($this->secretKeys[$this->activeKeyId])) {
			throw new ActiveKeyIdNotFoundException($this->activeKeyId);
		}
	}


	/**
	 * The key id and the marker go into what the decryption verifies, so changing them
	 * in the stored value makes decryption fail.
	 *
	 * @throws CannotPerformOperation
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidType
	 * @throws JsonException
	 * @throws SodiumException
	 * @throws TypeError
	 */
	public function encrypt(#[SensitiveParameter] string $data): string
	{
		[$secretKey, $publicKey] = $this->getKeyPair($this->activeKeyId);
		$boundData = $this->buildBoundAdditionalData($this->activeKeyId, FormatMarker::AuthenticatedPublicKeyV1);
		$cipherText = Crypto::encryptWithAD(new HiddenString($data), $secretKey, $publicKey, $boundData);
		return $this->formatKeyCipherText($this->activeKeyId, FormatMarker::AuthenticatedPublicKeyV1, $cipherText);
	}


	/**
	 * The key id and the marker are combined with the given additional data into what the decryption verifies,
	 * so changing them in the stored value makes decryption fail.
	 *
	 * @throws CannotPerformOperation
	 * @throws EncryptWithAdNeedsAdditionalDataException
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidType
	 * @throws JsonException
	 * @throws SodiumException
	 * @throws TypeError
	 */
	public function encryptWithAd(#[SensitiveParameter] string $data, string $additionalData): string
	{
		if ($additionalData === '') {
			throw new EncryptWithAdNeedsAdditionalDataException();
		}
		[$secretKey, $publicKey] = $this->getKeyPair($this->activeKeyId);
		$boundData = $this->buildBoundAdditionalData($this->activeKeyId, FormatMarker::AuthenticatedPublicKeyWithAdV1, $additionalData);
		$cipherText = Crypto::encryptWithAD(new HiddenString($data), $secretKey, $publicKey, $boundData);
		return $this->formatKeyCipherText($this->activeKeyId, FormatMarker::AuthenticatedPublicKeyWithAdV1, $cipherText);
	}


	/**
	 * @throws CannotPerformOperation
	 * @throws FormatMarkerMismatchException
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidSignature
	 * @throws InvalidType
	 * @throws SodiumException
	 * @throws TypeError
	 * @throws JsonException
	 * @throws UnknownEncryptionKeyIdException
	 * @throws UnknownFormatMarkerException
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 */
	public function decrypt(string $data): string
	{
		[$keyId, $marker, $cipherText] = $this->parseKeyCipherText($data);
		$validMarker = $this->checkFormatMarker($marker, FormatMarker::AuthenticatedPublicKeyV1);
		[$secretKey, $publicKey] = $this->getKeyPair($keyId);
		if ($validMarker === null) {
			// Data from before the marker existed, nothing was added to what the decryption verifies back then
			return Crypto::decrypt($cipherText, $secretKey, $publicKey)->getString();
		}
		$boundData = $this->buildBoundAdditionalData($keyId, $validMarker);
		return Crypto::decryptWithAD($cipherText, $secretKey, $publicKey, $boundData)->getString();
	}


	/**
	 * @throws CannotPerformOperation
	 * @throws DecryptWithAdNeedsAdditionalDataException
	 * @throws FormatMarkerMismatchException
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidSignature
	 * @throws InvalidType
	 * @throws SodiumException
	 * @throws TypeError
	 * @throws JsonException
	 * @throws UnknownEncryptionKeyIdException
	 * @throws UnknownFormatMarkerException
	 * @throws InvalidCipherTextFormatException
	 * @throws InvalidNumberOfComponentsException
	 */
	public function decryptWithAd(string $data, string $additionalData): string
	{
		if ($additionalData === '') {
			throw new DecryptWithAdNeedsAdditionalDataException();
		}
		[$keyId, $marker, $cipherText] = $this->parseKeyCipherText($data);
		$validMarker = $this->checkFormatMarker($marker, FormatMarker::AuthenticatedPublicKeyWithAdV1);
		[$secretKey, $publicKey] = $this->getKeyPair($keyId);
		if ($validMarker === null) {
			// Data from before the marker existed, the additional data was used alone back then
			return Crypto::decryptWithAD($cipherText, $secretKey, $publicKey, $additionalData)->getString();
		}
		$boundData = $this->buildBoundAdditionalData($keyId, $validMarker, $additionalData);
		return Crypto::decryptWithAD($cipherText, $secretKey, $publicKey, $boundData)->getString();
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
		return $this->needsReEncryptMarked($data, $this->activeKeyId, FormatMarker::AuthenticatedPublicKeyV1, FormatMarker::AuthenticatedPublicKeyWithAdV1);
	}


	/**
	 * The constructor guarantees a key id always has both keys, so one lookup can serve both.
	 *
	 * @return array{0:EncryptionSecretKey, 1:EncryptionPublicKey}
	 * @throws InvalidKey
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 */
	private function getKeyPair(string $keyId): array
	{
		if (isset($this->secretKeys[$keyId], $this->publicKeys[$keyId])) {
			return [new EncryptionSecretKey($this->secretKeys[$keyId]), new EncryptionPublicKey($this->publicKeys[$keyId])];
		} else {
			throw new UnknownEncryptionKeyIdException($keyId);
		}
	}

}
