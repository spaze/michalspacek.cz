<?php
namespace MichalSpacekCz;

abstract class BaseModel extends \Nette\Object
{

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \Bare\Formatter\Texy */
	protected $texyFormatter;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;


	public function __construct(\Nette\Database\Connection $connection, \Bare\Next\Formatter\Texy $texyFormatter, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $connection;
		$this->texyFormatter = $texyFormatter;
		$this->httpRequest = $httpRequest;
	}


}
