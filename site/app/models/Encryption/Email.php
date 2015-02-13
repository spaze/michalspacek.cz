<?php
namespace MichalSpacekCz\Encryption;

/**
 * Email encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Email extends \Nette\Object
{

	const GROUP = 'email';

	const CIPHER_NAME = MCRYPT_RIJNDAEL_128;

	const CIPHER_MODE = MCRYPT_MODE_CBC;

	/** @var \MichalSpacekCz\Encryption\Symmetric\StaticKey */
	protected $staticKeyEncryption;


	public function __construct(\MichalSpacekCz\Encryption\Symmetric\StaticKey $staticKeyEncryption)
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
