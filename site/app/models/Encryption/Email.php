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
