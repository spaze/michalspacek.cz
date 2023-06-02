<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routers;

use MichalSpacekCz\Blog\BlogPostLoader;
use Nette\Application\Routers\Route;
use Nette\Http\IRequest;

/**
 * The bidirectional route is responsible for mapping
 * HTTP request to a Request object for dispatch and vice-versa.
 */
class BlogPostRoute extends Route
{

	/**
	 * @param array<string, array<string, array<string, string>|string>> $metadata
	 */
	public function __construct(
		private readonly BlogPostLoader $blogPostLoader,
		string $mask,
		array $metadata = [],
		int $flags = 0,
	) {
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
