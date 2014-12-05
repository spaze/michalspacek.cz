<?php
namespace MichalSpacekCz;

/**
 * PasswordEncryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class PasswordEncryption extends \Nette\Object
{

	const GROUP = 'password';

	const CIPHER_NAME = MCRYPT_RIJNDAEL_128;

	const CIPHER_MODE = MCRYPT_MODE_CBC;

	/** @var \MichalSpacekCz\StaticKeyEncryption */
	protected $staticKeyEncryption;


	public function __construct(\MichalSpacekCz\StaticKeyEncryption $staticKeyEncryption)
	{
		$this->staticKeyEncryption = $staticKeyEncryption;
	}


	public function encrypt($data)
	{
		return $this->staticKeyEncryption->encrypt($data, self::GROUP, self::CIPHER_NAME, self::CIPHER_MODE);
	}


	public function decrypt($data)
	{
		return $this->staticKeyEncryption->decrypt($data, self::GROUP, self::CIPHER_NAME, self::CIPHER_MODE);
	}

}
