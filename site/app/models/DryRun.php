<?php
declare(strict_types = 1);

namespace MichalSpacekCz;

/**
 * DryRun model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class DryRun
{

	/** @var string */
	private const COOKIE_PATH = '/';

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


	public function setCookie(string $cookie): void
	{
		$this->cookie = $cookie;
	}


	public function setValue(string $value): void
	{
		$this->value = $value;
	}


	public function isEnabled(): bool
	{
		return ($this->httpRequest->getCookie($this->cookie) === $this->value);
	}


	public function enable(): void
	{
		$this->httpResponse->setCookie($this->cookie, $this->value, \Nette\Http\Response::PERMANENT, self::COOKIE_PATH);
	}


	public function disable(): void
	{
		$this->httpResponse->deleteCookie($this->cookie, self::COOKIE_PATH);
	}

}
