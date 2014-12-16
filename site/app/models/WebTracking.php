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

	const TRACKING_COOKIE = 'tracking';

	const TRACKING_DISABLED = 'donut';

	const TRACKING_PATH = '/';

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;


	public function __construct(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
	}


	public function isEnabled()
	{
		return ($this->httpRequest->getCookie(self::TRACKING_COOKIE) != self::TRACKING_DISABLED);
	}


	public function enable()
	{
		$this->httpResponse->deleteCookie(self::TRACKING_COOKIE, self::TRACKING_PATH);
	}


	public function disable()
	{
		$this->httpResponse->setCookie(self::TRACKING_COOKIE, self::TRACKING_DISABLED, \Nette\Http\Response::PERMANENT, self::TRACKING_PATH);
	}

}
