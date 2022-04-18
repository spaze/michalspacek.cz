<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routers;

use MichalSpacekCz\Post\Loader;
use Nette\Application\Routers\Route;
use Nette\Http\IRequest;

/**
 * The bidirectional route is responsible for mapping
 * HTTP request to a Request object for dispatch and vice-versa.
 */
class BlogPostRoute extends Route
{

	private Loader $blogPostLoader;


	/**
	 * @param Loader $blogPostLoader
	 * @param string $mask
	 * @param array<string, array<string, array<string, string>|string>> $metadata
	 * @param int $flags
	 */
	public function __construct(Loader $blogPostLoader, string $mask, array $metadata = [], int $flags = 0)
	{
		$this->blogPostLoader = $blogPostLoader;
		parent::__construct($mask, $metadata, $flags);
	}


	/**
	 * Maps HTTP request to a Request object.
	 *
	 * @param IRequest $httpRequest
	 * @return array<string, string>|null
	 */
	public function match(IRequest $httpRequest): ?array
	{
		$url = $httpRequest->getUrl();
		$previewKey = $url->getQueryParameter('preview');
		if (is_array($previewKey)) {
			return null;
		}
		return (!$this->blogPostLoader->exists(trim($url->getPath(), '/'), $previewKey) ? null : parent::match($httpRequest));
	}

}
