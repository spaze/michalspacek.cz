<?php
namespace MichalSpacekCz;

/**
 * StaticKeyEncryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class StaticKeyEncryption extends \Nette\Object
{

	const KEY_IV_CIPHERTEXT_SEPARATOR = ';';

	/** @var string[] */
	private $keys;

	/** @var string[] */
	private $activeKeyIds;

	/** @var \MichalSpacekCz\Encryption */
	protected $encryption;


	public function __construct(\MichalSpacekCz\Encryption $encryption)
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


	public function encrypt($data, $group, $cipherName, $cipherMode)
	{
		$keyId = $this->getActiveKeyId($group);
		$key = $this->getKey($group, $keyId);
		list($iv, $cipherText) = $this->encryption->encrypt($data, $key, $cipherName, $cipherMode);
		return $this->formatKeyIvCipherText($keyId, $iv, $cipherText);
	}


	public function decrypt($data, $group, $cipherName, $cipherMode)
	{
		list($keyId, $iv, $cipherText) = $this->parseKeyIvCipherText($data);
		$key = $this->getKey($group, $keyId);
		return $this->encryption->decrypt($cipherText, $key, $iv, $cipherName, $cipherMode);
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
