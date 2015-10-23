<?php
namespace MichalSpacekCz\Encryption\Symmetric;

use \Defuse\Crypto;

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

		try {
			$cipherText = Crypto\Crypto::encrypt($data, $key);
		} catch (Crypto\Exception\CryptoTestFailedException $e) {
			throw new \RuntimeException('Crypto test failed because: ' . $e->getMessage());
		} catch (Crypto\Exception\CannotPerformOperationException $e) {
			throw new \RuntimeException('Cannot encrypt because: ' . $e->getMessage());
		}

		return $this->formatKeyCipherText($keyId, $cipherText);
	}


	public function decrypt($data, $group)
	{
		list($keyId, $cipherText) = $this->parseKeyCipherText($data);
		$key = $this->getKey($group, $keyId);

		try {
			$plainText = Crypto\Crypto::decrypt($cipherText, $key);
		} catch (Crypto\Exception\InvalidCiphertextException $e) {
			throw new \RuntimeException('The ciphertext has been tampered with!');
		} catch (Crypto\Exception\CryptoTestFailedException $e) {
			throw new \RuntimeException('Crypto test failed because: ' . $e->getMessage());
		} catch (Crypto\Exception\CannotPerformOperationException $e) {
			throw new \RuntimeException('Cannot encrypt because: ' . $e->getMessage());
		}

		return $plainText;
	}


	private function getKey($group, $keyId)
	{
		if (isset($this->keys[$group][$keyId])) {
			return $this->keys[$group][$keyId];
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
		$data = explode(self::KEY_IV_CIPHERTEXT_SEPARATOR, $data);
		if (count($data) !== 3) {
			throw new \OutOfBoundsException('Data must have cipher, key, iv, and ciphertext. Now look at the Oxford comma!');
		}
		return array(base64_decode($data[1]), base64_decode($data[2]));
	}


	private function formatKeyCipherText($keyId, $cipherText)
	{
		$data = array(
			base64_encode($keyId),
			base64_encode($cipherText),
		);
		return self::KEY_IV_CIPHERTEXT_SEPARATOR . implode(self::KEY_IV_CIPHERTEXT_SEPARATOR, $data);
	}

}
