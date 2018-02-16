<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

/**
 * Password hash encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Password
{

	use \Nette\SmartObject;

	private const GROUP = 'password';

	/** @var \MichalSpacekCz\Encryption\Symmetric\StaticKey */
	protected $staticKeyEncryption;


	/**
	 * Setup the service.
	 *
	 * @param Symmetric\StaticKey $staticKeyEncryption
	 */
	public function __construct(Symmetric\StaticKey $staticKeyEncryption)
	{
		$this->staticKeyEncryption = $staticKeyEncryption;
	}


	/**
	 * Encrypt a password hash.
	 *
	 * @param string $data
	 * @return string
	 * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
	 * @throws \ParagonIE\Halite\Alerts\InvalidDigestLength
	 * @throws \ParagonIE\Halite\Alerts\InvalidKey
	 * @throws \ParagonIE\Halite\Alerts\InvalidMessage
	 * @throws \ParagonIE\Halite\Alerts\InvalidType
	 * @throws \TypeError
	 */
	public function encrypt(string $data): string
	{
		return $this->staticKeyEncryption->encrypt($data, self::GROUP);
	}


	/**
	 * Decrypt a password hash.
	 *
	 * @param string $data
	 * @return string
	 * @throws \ParagonIE\Halite\Alerts\CannotPerformOperation
	 * @throws \ParagonIE\Halite\Alerts\InvalidDigestLength
	 * @throws \ParagonIE\Halite\Alerts\InvalidKey
	 * @throws \ParagonIE\Halite\Alerts\InvalidMessage
	 * @throws \ParagonIE\Halite\Alerts\InvalidSignature
	 * @throws \ParagonIE\Halite\Alerts\InvalidType
	 * @throws \TypeError
	 */
	public function decrypt(string $data): string
	{
		return $this->staticKeyEncryption->decrypt($data, self::GROUP);
	}

}
