<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use LogicException;
use Nette\Application\Response;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\UI\Presenter;

class UiPresenterMock extends Presenter
{

	private ?Response $response = null;
	private bool $responseSent = false;


	/**
	 * @noinspection PhpMissingParentConstructorInspection Intentionally
	 */
	public function __construct()
	{
	}


	public function sendResponse(Response $response): never
	{
		$this->response = $response;
		$this->responseSent = true;
		$this->terminate();
	}


	public function getResponse(): Response
	{
		if (!$this->response) {
			throw new LogicException('Send response first with sendResponse()');
		}
		return $this->response;
	}


	public function isResponseSent(): bool
	{
		return $this->responseSent;
	}


	public function redirect(string $destination, $args = []): never
	{
		$this->sendResponse(new RedirectResponse($destination));
	}


	public function reset(): void
	{
		$this->responseSent = false;
	}

}