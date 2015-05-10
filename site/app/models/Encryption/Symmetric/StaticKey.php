<?php
namespace MichalSpacekCz\Encryption\Symmetric;

/**
 * StaticKey encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class StaticKey extends \Nette\Object
{

	const KEY_IV_CIPHERTEXT_SEPARATOR = '$';

	/** @var string[] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;

	/** @var \MichalSpacekCz\Encryption */
	protected $encryption;


	public function __construct(\MichalSpacekCz\Encryption\Encryption $encryption)
	{
		$this->encryption = $encryption;
	}


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


	public function encrypt($data, $group, $cipher)
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);
		list($iv, $cipherText) = $this->encryption->encrypt($data, $key, $cipher);
		return $this->formatKeyIvCipherText($cipher, $keyId, $iv, $cipherText);
	}


	public function decrypt($data, $group, $cipher)
	{
		list($cipher, $keyId, $iv, $cipherText) = $this->parseKeyIvCipherText($data);
		$key = $this->getKey($group, $keyId);
		return $this->encryption->decrypt($cipherText, $key, $cipher, $iv);
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
		if (count($data) !== 5) {
			throw new \OutOfBoundsException('Data must have cipher, key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array($data[1], base64_decode($data[2]), base64_decode($data[3]), base64_decode($data[4]));
	}


	private function formatKeyIvCipherText($cipher, $keyId, $iv, $cipherText)
	{
		return implode(self::KEY_IV_CIPHERTEXT_SEPARATOR, array($cipher, base64_encode($keyId), base64_encode($iv), base64_encode($cipherText)));
	}

}
