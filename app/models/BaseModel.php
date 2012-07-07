<?php
abstract class BaseModel extends \Nette\Database\Table\Selection
{
	const TABLE_NAME = null;


	public function __construct(\Nette\Database\Connection $connection)
	{
		parent::__construct(static::TABLE_NAME, $connection);
	}


}
