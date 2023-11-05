<?php
declare(strict_types = 1);

namespace Spaze\Session\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Spaze\Encryption\Symmetric\StaticKey;
use stdClass;

/**
 * @property stdClass $config
 */
class MysqlSessionHandlerExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'tableName' => Expect::string()->default('sessions'),
			'lockTimeout' => Expect::int()->default(5),
			'unchangedUpdateDelay' => Expect::int()->default(300),
			'encryptionService' => Expect::string(StaticKey::class),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$definition = $builder->addDefinition($this->prefix('sessionHandler'))
			->setType('Spaze\Session\MysqlSessionHandler')
			->addSetup('setTableName', [$this->config->tableName])
			->addSetup('setLockTimeout', [$this->config->lockTimeout])
			->addSetup('setUnchangedUpdateDelay', [$this->config->unchangedUpdateDelay]);

		if ($this->config->encryptionService) {
			$definition->addSetup('setEncryptionService', [$this->config->encryptionService]);
		}

		/** @var ServiceDefinition $sessionDefinition */
		$sessionDefinition = $builder->getDefinition('session');
		$sessionSetup = $sessionDefinition->getSetup();
		# Prepend setHandler method to other possible setups (setExpiration) which would start session prematurely
		array_unshift($sessionSetup, new Statement('setHandler', [$definition]));
		$sessionDefinition->setSetup($sessionSetup);
	}

}
