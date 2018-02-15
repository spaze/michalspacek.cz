<?php
namespace MichalSpacekCz\Encryption\Symmetric;

use ParagonIE\Halite;

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

	/** @var string[] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;


	public function setKeys($keys)
	{
		$this->keys = $keys;
	}


	public function setActiveKeyIds($activeKeyIds)
	{
		$this->activeKeyIds = $activeKeyIds;
	}


	/**
	 * Encrypt data.
	 *
	 * It's safe to throw exceptions here as the stack trace will not contain the key,
	 * because the key is not passed as a parameter to the function.
	 *
	 * @param string $data The plaintext
	 * @param string $group The group from which to read the key
	 * @return string
	 */
	public function encrypt($data, $group)
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);
		$cipherText = Halite\Symmetric\Crypto::encrypt(new Halite\HiddenString($data), $key);
		return $this->formatKeyCipherText($keyId, $cipherText);
	}


	public function decrypt($data, $group)
	{
		list($keyId, $cipherText) = $this->parseKeyCipherText($data);
		$key = $this->getKey($group, $keyId);
		return Halite\Symmetric\Crypto::decrypt($cipherText, $key)->getString();
	}


	/**
	 * Get encryption key.
	 *
	 * @param $group
	 * @param $keyId
	 * @return Halite\Symmetric\EncryptionKey
	 * @throws Halite\Alerts\InvalidKey
	 * @throws \TypeError
	 */
	private function getKey($group, $keyId): Halite\Symmetric\EncryptionKey
	{
		if (isset($this->keys[$group][$keyId])) {
			return new Halite\Symmetric\EncryptionKey(new Halite\HiddenString($this->keys[$group][$keyId]));
		} else {
			throw new \OutOfRangeException('Unknown encryption key id: ' . $keyId);
		}
	}


	private function getActiveKeyId($group)
	{
		return $this->activeKeyIds[$group];
	}


	private function parseKeyCipherText($data)
	{
		$data = explode(self::KEY_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 3) {
			throw new \OutOfBoundsException('Data must have cipher, key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array($data[1], $data[2]);
	}


	private function formatKeyCipherText($keyId, $cipherText)
	{
		return self::KEY_CIPHERTEXT_SEPARATOR . $keyId . self::KEY_CIPHERTEXT_SEPARATOR . $cipherText;
	}

}
