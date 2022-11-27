<?php
declare(strict_types = 1);

namespace Spaze\Exports\Bridges\Nette\Atom;

use Nette\Application\Response as NetteResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Spaze\Exports\Atom\Feed;

/**
 * Atom export response.
 *
 * @author Michal Špaček
 */
class Response implements NetteResponse
{

	public const CONTENT_TYPE = 'application/atom+xml';


	public function __construct(
		private Feed $feed,
	) {
	}


	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$httpResponse->setContentType('application/atom+xml', 'utf-8');
		$feed = (string)$this->feed;
		$httpResponse->setHeader('Content-Length', (string)strlen($feed));
		echo $feed;
	}

}
