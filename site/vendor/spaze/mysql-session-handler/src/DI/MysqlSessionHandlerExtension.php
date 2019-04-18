<?php
declare(strict_types = 1);

namespace Spaze\Session\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Statement;

class MysqlSessionHandlerExtension extends CompilerExtension
{

	private $defaults = [
		'tableName' => 'sessions',
		'lockTimeout' => 5,
		'unchangedUpdateDelay' => 300,
		'encryptionService' => null,
	];


	public function loadConfiguration(): void
	{
		$this->validateConfig($this->defaults);

		$builder = $this->getContainerBuilder();

		$definition = $builder->addDefinition($this->prefix('sessionHandler'))
			->setClass('Spaze\Session\MysqlSessionHandler')
			->addSetup('setTableName', [$this->config['tableName']])
			->addSetup('setLockTimeout', [$this->config['lockTimeout']])
			->addSetup('setUnchangedUpdateDelay', [$this->config['unchangedUpdateDelay']]);

		if ($this->config['encryptionService']) {
			$definition->addSetup('setEncryptionService', [$this->config['encryptionService']]);
		}

		$sessionDefinition = $builder->getDefinition('session');
		$sessionSetup = $sessionDefinition->getSetup();
		# Prepend setHandler method to other possible setups (setExpiration) which would start session prematurely
		array_unshift($sessionSetup, new Statement('setHandler', [$definition]));
		$sessionDefinition->setSetup($sessionSetup);
	}

}
