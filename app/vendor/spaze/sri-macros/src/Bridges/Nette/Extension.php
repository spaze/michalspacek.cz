<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Nette;

use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Spaze\SubresourceIntegrity\Bridges\Latte\LatteExtension;
use Spaze\SubresourceIntegrity\Bridges\Latte\Nodes\SriNodeFactory;
use Spaze\SubresourceIntegrity\FileBuilder;
use Spaze\SubresourceIntegrity\HashingAlgo;
use Spaze\SubresourceIntegrity\LocalMode;
use Spaze\SubresourceIntegrity\SriConfig;

class Extension extends CompilerExtension
{

	/** @var object{resources: array<string, string|array{url: string, hash: string|array<int, string>}>, localPrefix: object{url: string, path: string, build: string}, localMode: string, hashingAlgos: list<string>} */
	protected $config;


	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'resources' => Expect::anyOf(
				Expect::arrayOf(Expect::string()),
				Expect::structure([
					'url' => Expect::string(),
					'hash' => Expect::anyOf(
						Expect::string(),
						Expect::listOf(Expect::string()),
					),
				]),
			)->required(),
			'localPrefix' => Expect::structure([
				'url' => Expect::string(),
				'path' => Expect::string(),
				'build' => Expect::string(),
			])->required(),
			'localMode' => Expect::anyOf(...LocalMode::allModes())->default(LocalMode::Direct->value),
			'hashingAlgos' => Expect::listOf(Expect::anyOf(...HashingAlgo::allAlgos())),
		]);
	}


	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$builder->addDefinition($this->prefix('config'))
			->setType(SriConfig::class)
			->addSetup('setResources', [$this->config->resources])
			->addSetup('setLocalUrlPrefix', [$this->config->localPrefix->url])
			->addSetup('setLocalPathPrefix', [$this->config->localPrefix->path])
			->addSetup('setLocalBuildPrefix', [$this->config->localPrefix->build])
			->addSetup('setLocalMode', [$this->config->localMode])
			->addSetup('setHashingAlgos', [$this->config->hashingAlgos]);
		$builder->addDefinition($this->prefix('fileBuilder'))
			->setType(FileBuilder::class);
		$builder->addDefinition($this->prefix('nodeFactory'))
			->setType(SriNodeFactory::class);
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
