<?php
declare(strict_types = 1);

namespace Spaze\Exports\Bridges\Nette\Atom;

use Spaze\Exports\Atom;

/**
 * Atom export response.
 *
 * @author Michal Špaček
 */
class Response implements \Nette\Application\IResponse
{

	/** @var string */
	public const CONTENT_TYPE = 'application/atom+xml';

	/** @var Atom\Feed */
	protected $feed;


	/**
	 * Response constructor.
	 *
	 * @param Atom\Feed $feed
	 */
	public function __construct(Atom\Feed $feed)
	{
		$this->feed = $feed;
	}


	/**
	 * Sends response to output.
	 *
	 * @return void
	 */
	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse): void
	{
		$httpResponse->setContentType('application/atom+xml', 'utf-8');
		$feed = (string)$this->feed;
		$httpResponse->setHeader('Content-Length', (string)strlen($feed));
		echo $feed;
	}

}
