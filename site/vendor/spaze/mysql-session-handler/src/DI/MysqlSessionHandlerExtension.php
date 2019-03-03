<?php
declare(strict_types = 1);

namespace Spaze\Session\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Statement;

class MysqlSessionHandlerExtension extends CompilerExtension
{

	private $defaults = [
		'tableName' => 'sessions',
		'encryptionService' => null,
	];


	public function loadConfiguration(): void
	{
		parent::loadConfiguration();

		$config = $this->getConfig($this->defaults);

		$builder = $this->getContainerBuilder();

		$definition = $builder->addDefinition($this->prefix('sessionHandler'))
			->setClass('Spaze\Session\MysqlSessionHandler')
			->addSetup('setTableName', [$config['tableName']]);

		if ($config['encryptionService']) {
			$definition->addSetup('setEncryptionService', [$config['encryptionService']]);
		}

		$sessionDefinition = $builder->getDefinition('session');
		$sessionSetup = $sessionDefinition->getSetup();
		# Prepend setHandler method to other possible setups (setExpiration) which would start session prematurely
		array_unshift($sessionSetup, new Statement('setHandler', array($definition)));
		$sessionDefinition->setSetup($sessionSetup);
	}

}
