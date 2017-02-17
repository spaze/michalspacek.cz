<?php
namespace MichalSpacekCz\Encryption;

/**
 * Password encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Password
{

	use \Nette\SmartObject;

	const GROUP = 'password';

	/** @var \MichalSpacekCz\Encryption\Symmetric\StaticKey */
	protected $staticKeyEncryption;


	public function __construct(Symmetric\StaticKey $staticKeyEncryption)
	{
		$this->staticKeyEncryption = $staticKeyEncryption;
	}


	public function encrypt($data)
	{
		return $this->staticKeyEncryption->encrypt($data, self::GROUP);
	}


	public function decrypt($data)
	{
		return $this->staticKeyEncryption->decrypt($data, self::GROUP);
	}

}
