<?php
declare(strict_types = 1);

namespace Spaze\Exports\Bridges\Nette;

use Nette\Application\Response;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Spaze\Exports\Atom\AtomFeed;
use Spaze\Exports\Atom\AtomResponseContentType;

class AtomResponse implements Response
{

	public function __construct(
		private AtomFeed $feed,
	) {
	}


	public function send(IRequest $httpRequest, IResponse $httpResponse): void
	{
		$httpResponse->setContentType(AtomResponseContentType::ApplicationAtomXml->value, 'utf-8');
		$feed = (string)$this->feed;
		$httpResponse->setHeader('Content-Length', (string)strlen($feed));
		echo $feed;
	}

}
