<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Routing;

use MichalSpacekCz\Articles\Blog\BlogPostLoader;
use Nette\Application\Routers\Route;
use Nette\Http\IRequest;
use Override;

/**
 * The bidirectional route is responsible for mapping
 * HTTP request to a Request object for dispatch and vice-versa.
 */
final class BlogPostRoute extends Route
{

	/**
	 * @param array<string, array<string, array<string, string>|string>> $metadata
	 */
	public function __construct(
		private readonly BlogPostLoader $blogPostLoader,
		string $mask,
		array $metadata = [],
	) {
		parent::__construct($mask, $metadata);
	}


	/**
	 * Maps HTTP request to a Request object.
	 *
	 * @param IRequest $httpRequest
	 * @return array<array-key, mixed>|null
	 */
	#[Override]
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
