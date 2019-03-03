<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption\Symmetric;

use ParagonIE\Halite;
use ParagonIE\HiddenString\HiddenString;

/**
 * StaticKey encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class StaticKey
{

	use \Nette\SmartObject;

	private const KEY_CIPHERTEXT_SEPARATOR = '$';

	/** @var string[][] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;


	/**
	 * Set keys.
	 *
	 * @param string[][] $keys
	 */
	public function setKeys(array $keys): void
	{
		$this->keys = $keys;
	}


	/**
	 * Set active key ids.
	 *
	 * Active keys are the ones used when encrypting.
	 *
	 * @param string[] $activeKeyIds
	 */
	public function setActiveKeyIds(array $activeKeyIds): void
	{
		$this->activeKeyIds = $activeKeyIds;
	}


	/**
	 * Encrypt data using symmetric encryption.
	 *
	 * It's safe to throw exceptions here as the stack trace will not contain the key,
	 * because the key is not passed as a parameter to the function.
	 *
	 * @param string $data The plaintext
	 * @param string $group The group from which to read the key
	 * @return string
	 * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
	 * @throws \ParagonIE\Halite\Alerts\InvalidDigestLength
	 * @throws \ParagonIE\Halite\Alerts\InvalidKey
	 * @throws \ParagonIE\Halite\Alerts\InvalidMessage
	 * @throws \ParagonIE\Halite\Alerts\InvalidType
	 * @throws \TypeError
	 */
	public function encrypt(string $data, string $group): string
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);
		$cipherText = Halite\Symmetric\Crypto::encrypt(new HiddenString($data), $key);
		return $this->formatKeyCipherText($keyId, $cipherText);
	}


	/**
	 * Decrypt data using symmetric encryption.
	 *
	 * @param string $data
	 * @param string $group
	 * @return string
	 * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
	 * @throws \ParagonIE\Halite\Alerts\InvalidDigestLength
	 * @throws \ParagonIE\Halite\Alerts\InvalidKey
	 * @throws \ParagonIE\Halite\Alerts\InvalidMessage
	 * @throws \ParagonIE\Halite\Alerts\InvalidSignature
	 * @throws \ParagonIE\Halite\Alerts\InvalidType
	 * @throws \TypeError
	 */
	public function decrypt(string $data, string $group): string
	{
		list($keyId, $cipherText) = $this->parseKeyCipherText($data);
		$key = $this->getKey($group, $keyId);
		return Halite\Symmetric\Crypto::decrypt($cipherText, $key)->getString();
	}


	/**
	 * Get encryption key.
	 *
	 * @param string $group
	 * @param string $keyId
	 * @return Halite\Symmetric\EncryptionKey
	 * @throws \ParagonIE\Halite\Alerts\InvalidKey
	 * @throws \TypeError
	 */
	private function getKey(string $group, string $keyId): Halite\Symmetric\EncryptionKey
	{
		if (isset($this->keys[$group][$keyId])) {
			return new Halite\Symmetric\EncryptionKey(new HiddenString($this->keys[$group][$keyId]));
		} else {
			throw new \OutOfRangeException('Unknown encryption key id: ' . $keyId);
		}
	}


	/**
	 * Get active key id.
	 *
	 * Active key is used when encrypting.
	 *
	 * @param string $group
	 * @return string
	 */
	private function getActiveKeyId(string $group): string
	{
		return $this->activeKeyIds[$group];
	}


	/**
	 * Parse text into key id and ciphertext.
	 *
	 * @param string $data
	 * @return string[]
	 */
	private function parseKeyCipherText(string $data): array
	{
		$data = explode(self::KEY_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 3) {
			throw new \OutOfBoundsException('Data must have cipher, key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array($data[1], $data[2]);
	}


	/**
	 * Format string to store into database.
	 *
	 * @param string $keyId
	 * @param string $cipherText
	 * @return string
	 */
	private function formatKeyCipherText(string $keyId, string $cipherText): string
	{
		return self::KEY_CIPHERTEXT_SEPARATOR . $keyId . self::KEY_CIPHERTEXT_SEPARATOR . $cipherText;
	}

}
