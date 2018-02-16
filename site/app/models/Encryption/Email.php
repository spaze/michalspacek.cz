<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

/**
 * Email encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Email
{

	use \Nette\SmartObject;

	private const GROUP = 'email';

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
	 * Encrypt an email address.
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
	 * Decrypt an email address.
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
