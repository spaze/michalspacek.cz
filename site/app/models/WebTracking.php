<?php
namespace MichalSpacekCz;

/**
 * WebTracking model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class WebTracking
{

	const TRACKING_PATH = '/';

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var string */
	private $cookie;

	/** @var string */
	private $value;


	public function __construct(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
	}


	public function setCookie($cookie)
	{
		$this->cookie = $cookie;
	}


	public function setValue($value)
	{
		$this->value = $value;
	}


	public function isEnabled()
	{
		return ($this->httpRequest->getCookie($this->cookie) != $this->value);
	}


	public function enable()
	{
		$this->httpResponse->deleteCookie($this->cookie, self::TRACKING_PATH);
	}


	public function disable()
	{
		$this->httpResponse->setCookie($this->cookie, $this->value, \Nette\Http\Response::PERMANENT, self::TRACKING_PATH);
	}

}
