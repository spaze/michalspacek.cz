<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator\Bridges\Nette;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Spaze\NonceGenerator\Generator;

class GeneratorExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		$this->getContainerBuilder()
			->addDefinition($this->prefix('generator'))
			->setClass(Generator::class);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$register = function (FactoryDefinition $service) {
			$service->getResultDefinition()->addSetup('addProvider', ['nonceGenerator', $this->prefix('@generator')]);
		};

		// A string is used in getByType() instead of ::class so we don't need to depend on nette/application
		$latteFactoryService = $builder->getByType('\Nette\Bridges\ApplicationLatte\ILatteFactory') ?: 'nette.latteFactory';
		if ($builder->hasDefinition($latteFactoryService)) {
			/** @var FactoryDefinition $definition */
			$definition = $builder->getDefinition($latteFactoryService);
			$register($definition);
		}

		if ($builder->hasDefinition('nette.latte')) {
			/** @var FactoryDefinition $definition */
			$definition = $builder->getDefinition('nette.latte');
			$register($definition);
		}
	}

}
