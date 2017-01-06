<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * KeyCDN service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class KeyCdn
{

	/** @var string */
	private $secretKey;

	/** @var integer */
	private $tokenExpiration;


	/**
	 * Set secret key.
	 *
	 * @param string $secretKey
	 */
	public function setSecretKey(string $secretKey): void
	{
		$this->secretKey = $secretKey;
	}


	/**
	 * Token will expire in this many seconds.
	 *
	 * @param integer $tokenExpiration
	 */
	public function setTokenExpiration(int $tokenExpiration): void
	{
		$this->tokenExpiration = $tokenExpiration;
	}


	/**
	 * Sign URL.
	 *
	 * @param string $toSign
	 * @return string Signed URL
	 */
	public function signUrl(string $toSign)
	{
		$url = new \Nette\Http\Url($toSign);
		$expire = time() + $this->tokenExpiration;
		$token = base64_encode(md5($url->getPath() . $this->secretKey . $expire, true));
		$token = str_replace(['+', '/', '='], ['-', '_', ''], $token);
		$url->appendQuery(['token' => $token, 'expire' => $expire]);
		return $url->getAbsoluteUrl();
	}

}
