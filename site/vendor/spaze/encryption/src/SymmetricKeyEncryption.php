<?php
declare(strict_types = 1);

namespace Spaze\Encryption;

use ParagonIE\ConstantTime\Hex;
use ParagonIE\Halite\Alerts\CannotPerformOperation;
use ParagonIE\Halite\Alerts\InvalidDigestLength;
use ParagonIE\Halite\Alerts\InvalidKey;
use ParagonIE\Halite\Alerts\InvalidMessage;
use ParagonIE\Halite\Alerts\InvalidSignature;
use ParagonIE\Halite\Alerts\InvalidType;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\Halite\Symmetric\EncryptionKey;
use ParagonIE\HiddenString\HiddenString;
use SensitiveParameter;
use SodiumException;
use Spaze\Encryption\Exceptions\InvalidKeyPrefixException;
use Spaze\Encryption\Exceptions\InvalidNumberOfComponentsException;
use Spaze\Encryption\Exceptions\UnknownEncryptionKeyIdException;
use TypeError;
use function count;
use function explode;

class SymmetricKeyEncryption
{

	private const KEY_CIPHERTEXT_SEPARATOR = '$';

	private const KEY_PREFIX_SEPARATOR = '_';

	/** @var array<string, HiddenString> */
	private array $keys = [];


	/**
	 * @param array<string, string> $keys key id => key
	 * @throws InvalidKeyPrefixException
	 */
	public function __construct(
		#[SensitiveParameter] array $keys,
		private string $activeKeyId,
		private string $keyPrefix,
	) {
		$keyPrefix = $this->keyPrefix . self::KEY_PREFIX_SEPARATOR;
		foreach ($keys as $id => $key) {
			if (str_starts_with($key, $keyPrefix)) {
				$this->keys[$id] = new HiddenString(Hex::decode(str_replace($keyPrefix, '', $key)));
			} else {
				$pos = strpos($key, self::KEY_PREFIX_SEPARATOR);
				throw new InvalidKeyPrefixException($id, $this->keyPrefix, $pos !== false ? substr($key, 0, $pos) : null);
			}
		}
	}


	/**
	 * @throws CannotPerformOperation
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidType
	 * @throws SodiumException
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 */
	public function encrypt(#[SensitiveParameter] string $data): string
	{
		$key = $this->getKey($this->activeKeyId);
		$cipherText = Crypto::encrypt(new HiddenString($data), $key);
		return $this->formatKeyCipherText($this->activeKeyId, $cipherText);
	}


	/**
	 * @throws CannotPerformOperation
	 * @throws InvalidDigestLength
	 * @throws InvalidKey
	 * @throws InvalidMessage
	 * @throws InvalidSignature
	 * @throws InvalidType
	 * @throws SodiumException
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 * @throws InvalidNumberOfComponentsException
	 */
	public function decrypt(string $data): string
	{
		[$keyId, $cipherText] = $this->parseKeyCipherText($data);
		$key = $this->getKey($keyId);
		return Crypto::decrypt($cipherText, $key)->getString();
	}


	/**
	 * Checks if the given data are encrypted using the active key.
	 *
	 * @throws InvalidNumberOfComponentsException
	 */
	public function needsReEncrypt(string $data): bool
	{
		[$keyId] = $this->parseKeyCipherText($data);
		return $keyId !== $this->activeKeyId;
	}


	/**
	 * @throws InvalidKey
	 * @throws TypeError
	 * @throws UnknownEncryptionKeyIdException
	 */
	private function getKey(string $keyId): EncryptionKey
	{
		if (isset($this->keys[$keyId])) {
			return new EncryptionKey($this->keys[$keyId]);
		} else {
			throw new UnknownEncryptionKeyIdException($keyId);
		}
	}


	/**
	 * @return array{0:string, 1:string}
	 * @throws InvalidNumberOfComponentsException
	 */
	private function parseKeyCipherText(string $data): array
	{
		$data = explode(self::KEY_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 3) {
			throw new InvalidNumberOfComponentsException();
		}
		return [$data[1], $data[2]];
	}


	private function formatKeyCipherText(string $keyId, string $cipherText): string
	{
		return self::KEY_CIPHERTEXT_SEPARATOR . $keyId . self::KEY_CIPHERTEXT_SEPARATOR . $cipherText;
	}

}
