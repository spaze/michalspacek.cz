<?php
declare(strict_types = 1);

namespace Spaze\NonceGenerator\Bridges\Nette;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Spaze\NonceGenerator\Bridges\Latte\LatteExtension;
use Spaze\NonceGenerator\Generator;

class GeneratorExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('generator'))
			->setType(Generator::class);
		$builder->addDefinition($this->prefix('nonce'))
			->setFactory([$this->prefix('@generator'), 'createNonce']);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$extension = $builder->addDefinition($this->prefix('latte.extension'))->setFactory(LatteExtension::class);
		$service = $builder->getByType(TemplateFactory::class);
		if ($service) {
			/** @var ServiceDefinition $definition */
			$definition = $builder->getDefinition($service);
			$definition->addSetup('?->onCreate[] = function (' . Template::class . ' $template): void { $template->getLatte()->addExtension(?); }', ['@self', $extension]);
		}
	}

}
