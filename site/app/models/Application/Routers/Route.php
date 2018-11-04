<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routers;

/**
 * The bidirectional route is responsible for mapping
 * HTTP request to a Request object for dispatch and vice-versa.
 */
class Route extends \Nette\Application\Routers\Route
{

	/** @var \MichalSpacekCz\Post\Loader */
	public $blogPostLoader;


	/**
	 * @param \MichalSpacekCz\Post\Loader $blogPostLoader
	 * @param string $mask
	 * @param string[] $metadata
	 * @param integer $flags
	 */
	public function __construct(\MichalSpacekCz\Post\Loader $blogPostLoader, string $mask, array $metadata = [], int $flags = 0)
	{
		$this->blogPostLoader = $blogPostLoader;
		parent::__construct($mask, $metadata, $flags);
	}


	/**
	 * Maps HTTP request to a Request object.
	 * @param \Nette\Http\IRequest $httpRequest
	 * @return \Nette\Application\Request|null
	 */
	public function match(\Nette\Http\IRequest $httpRequest): ?\Nette\Application\Request
	{
		$url = $httpRequest->getUrl();
		return (!$this->blogPostLoader->exists(trim($url->getPath(), '/'), $url->getQueryParameter('preview')) ? null : parent::match($httpRequest));
	}

}
