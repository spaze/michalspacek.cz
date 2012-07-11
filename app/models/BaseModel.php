<?php
namespace MichalSpacekCz;

abstract class BaseModel extends \Nette\Object
{

	/** @var \Nette\Database\Connection */
	protected $database;


	public function __construct(\Nette\Database\Connection $connection)
	{
		$this->database = $connection;
	}


}
