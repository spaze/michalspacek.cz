<?php
declare(strict_types = 1);

namespace Spaze\SvgIcons;

use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Spaze\SvgIcons\Nodes\IconNodeFactory;
use stdClass;

class NetteExtension extends CompilerExtension
{

	/** @var stdClass */
	protected $config;


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'iconsDir' => Expect::string()->required(),
			'cssClass' => Expect::string(),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('iconNodeFactory'))
			->setFactory(IconNodeFactory::class, [$this->config->iconsDir, $this->config->cssClass]);
	}


	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$latteFactoryService = $builder->getByType(LatteFactory::class) ?: 'nette.latteFactory';
		/** @var FactoryDefinition $service */
		$service = $builder->getDefinition($latteFactoryService);
		$extension = $builder->addDefinition($this->prefix('latte.extension'))->setFactory(LatteExtension::class);
		$service->getResultDefinition()->addSetup('addExtension', [$extension]);
	}

}
