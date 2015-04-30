<?php
namespace MichalSpacekCz\Encryption;

/**
 * Encryption service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Encryption extends \Nette\Object
{

	/** @var string */
	const CIPHER_AES_256_CBC = 'AES-256-CBC';

	/** @var \MichalSpacekCz\Encryption\Adapter\AdapterInterface */
	protected $adapter;


	public function __construct(Adapter\AdapterInterface $adapter)
	{
		$this->adapter = $adapter;
	}


	public function encrypt($clearText, $key, $cipher)
	{
		$iv = $this->adapter->createIv($this->adapter->getIvLength($cipher));
		$cipherText = $this->adapter->encrypt($clearText, $key, $cipher, $iv);
		return array($iv, $cipherText);
	}


	public function decrypt($cipherText, $key, $cipher, $iv)
	{
		return $this->adapter->decrypt($cipherText, $key, $cipher, $iv);
	}

}
