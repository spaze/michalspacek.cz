<?php
namespace MichalSpacekCz;

/**
 * Encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Encryption
{

	const CIPHER_NAME = MCRYPT_RIJNDAEL_128;

	const CIPHER_MODE = MCRYPT_MODE_CBC;

	const KEY_IV_CIPHERTEXT_SEPARATOR = ';';

	const GROUP_PASSWORD = 'password';

	/** @var string[] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;


	public function setKeys($keys)
	{
		foreach ($keys as $group) {
			foreach ($group as $key) {
				if (strlen($key) != 64 || !ctype_xdigit($key)) {
					throw new \InvalidArgumentException('Key must be 64 characters long and only consist of hexadecimal characters');
				}
			}
		}
		$this->keys = $keys;
	}


	public function setActiveKeyIds($activeKeyIds)
	{
		$this->activeKeyIds = $activeKeyIds;
	}


	public function encrypt($data, $group)
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(self::CIPHER_NAME, self::CIPHER_MODE), MCRYPT_DEV_URANDOM);
		$cipherText = mcrypt_encrypt(self::CIPHER_NAME, $key, $data, self::CIPHER_MODE, $iv);
		return $this->formatKeyIvCipherText($keyId, $iv, $cipherText);
	}


	public function decrypt($data, $group)
	{
		list($keyId, $iv, $cipherText) = $this->parseKeyIvCipherText($data);
		$key = $this->getKey($group, $keyId);
		$clearText = mcrypt_decrypt(self::CIPHER_NAME, $key, $cipherText, self::CIPHER_MODE, $iv);
		return rtrim($clearText, "\0");
	}


	private function getKey($group, $keyId)
	{
		if (isset($this->keys[$group][$keyId])) {
			return pack('H64', $this->keys[$group][$keyId]);
		} else {
			throw new \OutOfRangeException('Unknown encryption key id: ' . $keyId);
		}
	}


	private function getActiveKeyId($group)
	{
		return $this->activeKeyIds[$group];
	}


	private function parseKeyIvCipherText($data)
	{
		$data = explode(self::KEY_IV_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 3) {
			throw new \OutOfBoundsException('Data must have key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array_map('base64_decode', $data);
	}


	private function formatKeyIvCipherText($keyId, $iv, $cipherText)
	{
		return implode(self::KEY_IV_CIPHERTEXT_SEPARATOR, array(base64_encode($keyId), base64_encode($iv), base64_encode($cipherText)));
	}


}