<?php
namespace MichalSpacekCz\Encryption\Adapter;

use MichalSpacekCz\Encryption\Encryption;

/**
 * MCrypt encryption adapter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class MCrypt implements \MichalSpacekCz\Encryption\Adapter\AdapterInterface
{

	public function encrypt($clearText, $key, $cipher, $iv)
	{
		list($name, $mode) = $this->cipherToNameMode($cipher);
		return mcrypt_encrypt($name, $key, $clearText, $mode, $iv);
	}


	public function decrypt($cipherText, $key, $cipher, $iv)
	{
		list($name, $mode) = $this->cipherToNameMode($cipher);
		$clearText = mcrypt_decrypt($name, $key, $cipherText, $mode, $iv);
		return rtrim($clearText, "\0");
	}


	public function getIvLength($cipher)
	{
		list($name, $mode) = $this->cipherToNameMode($cipher);
		return mcrypt_get_iv_size($name, $mode);
	}


	public function createIv($length)
	{
		return mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
	}


	/**
	 * Cipher to cipher name and cipher mode.
	 *
	 * @param $cipher
	 * @return array of cipher name and cipher mode
	 */
	private function cipherToNameMode($cipher)
	{
		switch ($cipher) {
			case Encryption::CIPHER_AES_256_CBC:
				$result = array(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
				break;
			default:
				throw new \RuntimeException("Unknown cipher $cipher");
				break;
		}
		return $result;
	}

}