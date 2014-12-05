<?php
namespace MichalSpacekCz;

/**
 * Encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Encryption extends \Nette\Object
{

	public function encrypt($clearText, $key, $cipherName, $cipherMode)
	{
		$iv = mcrypt_create_iv(mcrypt_get_iv_size($cipherName, $cipherMode), MCRYPT_DEV_URANDOM);
		$cipherText = mcrypt_encrypt($cipherName, $key, $clearText, $cipherMode, $iv);
		return array($iv, $cipherText);
	}


	public function decrypt($cipherText, $key, $iv, $cipherName, $cipherMode)
	{
		$clearText = mcrypt_decrypt($cipherName, $key, $cipherText, $cipherMode, $iv);
		return rtrim($clearText, "\0");
	}


}