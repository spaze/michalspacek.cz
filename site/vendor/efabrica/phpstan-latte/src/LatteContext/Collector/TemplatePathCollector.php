<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\LatteContext\Collector;

use Efabrica\PHPStanLatte\LatteContext\CollectedData\CollectedTemplatePath;
use Efabrica\PHPStanLatte\LatteContext\Collector\TemplatePathCollector\TemplatePathCollectorInterface;
use Efabrica\PHPStanLatte\PhpDoc\LattePhpDocResolver;
use Efabrica\PHPStanLatte\Resolver\NameResolver\NameResolver;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @extends AbstractLatteContextCollector<Node, CollectedTemplatePath>
 */
final class TemplatePathCollector extends AbstractLatteContextCollector
{
    /** @var TemplatePathCollectorInterface[] */
    private array $templatePathCollectors;

    private LattePhpDocResolver $lattePhpDocResolver;

    /**
     * @param TemplatePathCollectorInterface[] $templatePathCollectors
     */
    public function __construct(
        array $templatePathCollectors,
        NameResolver $nameResolver,
        ReflectionProvider $reflectionProvider,
        LattePhpDocResolver $lattePhpDocResolver
    ) {
        parent::__construct($nameResolver, $reflectionProvider);
        $this->templatePathCollectors = $templatePathCollectors;
        $this->lattePhpDocResolver = $lattePhpDocResolver;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @phpstan-return null|CollectedTemplatePath[]
     */
    public function collectData(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();
        if ($classReflection === null) {
            return null;
        }

        $functionName = $scope->getFunctionName();
        if ($functionName === null) {
            return null;
        }

        if ($this->lattePhpDocResolver->resolveForNode($node, $scope)->isIgnored()) {
            return null;
        }

        $paths = null;
        $isCollected = false;
        foreach ($this->templatePathCollectors as $templatePathCollector) {
            if (!$templatePathCollector->isSupported($node)) {
                continue;
            }
            $isCollected = true;
            $collectedPaths = $templatePathCollector->collect($node, $scope);
            if (is_array($collectedPaths)) {
                $paths = array_merge($paths === null ? [] : $paths, $collectedPaths);
            }
        }

        if ($isCollected === false) {
            return null;
        }

        $actualClassName = $classReflection->getName();
        if ($paths === null) {
            // failed to resolve
            return [new CollectedTemplatePath($actualClassName, $functionName, null)];
        }
        $paths = array_unique($paths);
        $templatePaths = [];
        foreach ($paths as $path) {
            $templatePaths[] = new CollectedTemplatePath($actualClassName, $functionName, $path);
        }
        return $templatePaths;
    }
}