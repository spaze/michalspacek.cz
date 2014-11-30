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

	/** @var \MichalSpacekCz\Encryption */
	protected $encryption;


	public function __construct(\MichalSpacekCz\Encryption $encryption)
	{
		$this->encryption = $encryption;
	}


	public function encrypt($data)
	{
		return $this->encryption->encrypt($data, self::GROUP, self::CIPHER_NAME, self::CIPHER_MODE);
	}


	public function decrypt($data)
	{
		return $this->encryption->decrypt($data, self::GROUP, self::CIPHER_NAME, self::CIPHER_MODE);
	}

}
