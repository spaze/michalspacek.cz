<?php
namespace MichalSpacekCz;

abstract class BaseModel extends \Nette\Object
{

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \Bare\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Connection $connection, \Bare\Next\Formatter\Texy $texyFormatter)
	{
		$this->database = $connection;
		$this->texyFormatter = $texyFormatter;
	}


}
