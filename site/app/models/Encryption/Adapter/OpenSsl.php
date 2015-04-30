<?php
namespace MichalSpacekCz\Encryption\Adapter;

/**
 * OpenSSL encryption adapter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class OpenSsl implements AdapterInterface
{

	public function encrypt($clearText, $key, $cipher, $iv)
	{
		return openssl_encrypt($clearText, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	}


	public function decrypt($cipherText, $key, $cipher, $iv)
	{
		return openssl_decrypt($cipherText, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	}


	public function getIvLength($cipher)
	{
		return openssl_cipher_iv_length($cipher);
	}


	public function createIv($length)
	{
		$random = false;
		$strong = false;

		$i = 0;
		while (!$strong && $i < 10) {
			$random = openssl_random_pseudo_bytes($length, $strong);
			$i++;
		}

		if ($random === false) {
			throw new \RuntimeException("Error creating IV, tried $i times");
		}

		return $random;
	}

}