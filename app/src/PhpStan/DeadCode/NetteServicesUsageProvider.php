<?php
declare(strict_types = 1);

namespace MichalSpacekCz\PhpStan\DeadCode;

use Composer\Pcre\Preg;
use LogicException;
use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * Marks members of classes registered as Nette DI services as used by the dead-code detector.
 *
 * Nette DI instantiates services dynamically (`new $serviceClass(...)` where the class name is
 * a class-string), which the dead-code detector cannot see statically. Without a provider like
 * this one, those constructors would be reported as dead.
 *
 * Service definition shapes recognised in the `services:` block:
 *   - "ClassName" / name: ClassName (scalar FQCN)
 *   - "ClassName(args)" / name: ClassName(args) (NEON Entity, .value is the FQCN)
 *   - name: { create: ClassName } (recurses into create)
 *   - name: { factory: ClassName } (recurses into factory)
 *   - name: { implement: InterfaceName } (recurses into implement; for the same automatically
 *     generated factory handling as a bare interface registration)
 *
 * When a registered class is an interface with a create() method, the return type of create()
 * is also marked as used - Nette DI auto-generates a class implementing the interface whose
 * create() does `new ReturnType(...)`, and that `new` has no statically visible call site.
 *
 * Not supported:
 *   - @serviceName::method references (factory-call form, not an instantiation here)
 *   - type: + imported: (service imported from elsewhere, not instantiated by this entry)
 */
final class NetteServicesUsageProvider implements MemberUsageProvider
{

	/** @var array<class-string, true> */
	private array $services = [];


	/**
	 * @param list<string> $paths relative or absolute paths to NEON files
	 */
	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		array $paths,
	) {
		foreach ($paths as $path) {
			$this->loadFromNeon($path);
		}
	}


	#[Override]
	public function getUsages(Node $node, Scope $scope): array
	{
		if (!$node instanceof InClassNode) { // @phpstan-ignore phpstanApi.instanceofAssumption
			return [];
		}
		$classReflection = $node->getClassReflection();
		$className = $classReflection->getName();
		if (!isset($this->services[$className])) {
			return [];
		}
		$nativeReflection = $classReflection->getNativeReflection();
		if (!$nativeReflection->hasMethod('__construct')) {
			return [];
		}
		$declaringClass = $nativeReflection->getMethod('__construct')->getDeclaringClass()->getName();
		return [
			new ClassMethodUsage(
				UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Instantiated by Nette DI container')),
				new ClassMethodRef($declaringClass, '__construct', possibleDescendant: false),
			),
		];
	}


	/**
	 * Pulls FQCNs out of the `services:` block of a NEON string. Static so it can be
	 * unit-tested without a ReflectionProvider; the reflection check + auto-impl factory
	 * return-type resolution stay in the instance.
	 *
	 * @return list<class-string>
	 */
	public static function findServiceClassesInNeon(string $neonContent): array
	{
		$classes = [];
		$decoded = Neon::decode($neonContent);
		if (!is_array($decoded) || !isset($decoded['services']) || !is_array($decoded['services'])) {
			return [];
		}
		foreach ($decoded['services'] as $entry) {
			$class = self::resolveServiceClass($entry);
			if ($class !== null) {
				$classes[] = $class;
			}
		}
		return $classes;
	}


	private function loadFromNeon(string $neonFile): void
	{
		$contents = file_get_contents($neonFile);
		if ($contents === false) {
			throw new LogicException(sprintf('NEON file %s does not exist or is not readable', $neonFile));
		}
		foreach (self::findServiceClassesInNeon($contents) as $class) {
			if ($this->reflectionProvider->hasClass($class)) {
				$this->markServiceClass($class);
			}
		}
	}


	/**
	 * @param class-string $class
	 */
	private function markServiceClass(string $class): void
	{
		$this->services[$class] = true;

		// Automatically generated factory interfaces: Nette DI generates a class implementing
		// the interface whose create() returns `new ReturnType(...)`. The return type's
		// constructor also needs to be marked as used - there's no statically visible `new`
		// for it anywhere in PHP source.
		$classReflection = $this->reflectionProvider->getClass($class);
		if (!$classReflection->isInterface() || !$classReflection->hasNativeMethod('create')) {
			return;
		}
		$createMethod = $classReflection->getNativeMethod('create');
		foreach ($createMethod->getVariants() as $variant) {
			foreach ($variant->getReturnType()->getObjectClassNames() as $returnedClass) {
				if ($this->reflectionProvider->hasClass($returnedClass)) {
					$this->services[$returnedClass] = true;
				}
			}
		}
	}


	/**
	 * @return class-string|null
	 */
	private static function resolveServiceClass(mixed $value): ?string
	{
		if (is_string($value)) {
			return self::extractClassName($value);
		}
		if ($value instanceof Entity && is_string($value->value)) {
			return self::extractClassName($value->value);
		}
		if (is_array($value)) {
			if (isset($value['create'])) {
				return self::resolveServiceClass($value['create']);
			}
			if (isset($value['factory'])) {
				return self::resolveServiceClass($value['factory']);
			}
			if (isset($value['implement'])) {
				return self::resolveServiceClass($value['implement']);
			}
		}
		return null;
	}


	/**
	 * @return class-string|null
	 */
	private static function extractClassName(string $value): ?string
	{
		if (!Preg::isMatch('/^\\\\?[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $value)) {
			return null;
		}
		/** @var class-string $normalised */
		$normalised = ltrim($value, '\\');
		return $normalised;
	}

}
