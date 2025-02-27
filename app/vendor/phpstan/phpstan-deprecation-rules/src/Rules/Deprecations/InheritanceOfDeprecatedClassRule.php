<?php declare(strict_types = 1);

namespace PHPStan\Rules\Deprecations;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<Class_>
 */
class InheritanceOfDeprecatedClassRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	private DeprecatedScopeHelper $deprecatedScopeHelper;

	public function __construct(ReflectionProvider $reflectionProvider, DeprecatedScopeHelper $deprecatedScopeHelper)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->deprecatedScopeHelper = $deprecatedScopeHelper;
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->deprecatedScopeHelper->isScopeDeprecated($scope)) {
			return [];
		}

		if ($node->extends === null) {
			return [];
		}

		$errors = [];

		$className = isset($node->namespacedName)
			? (string) $node->namespacedName
			: (string) $node->name;

		try {
			$class = $this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException $e) {
			return [];
		}

		$parentClassName = (string) $node->extends;

		try {
			$parentClass = $this->reflectionProvider->getClass($parentClassName);
			$description = $parentClass->getDeprecatedDescription();
			if ($parentClass->isDeprecated()) {
				if (!$class->isAnonymous()) {
					if ($description === null) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Class %s extends deprecated class %s.',
							$className,
							$parentClassName,
						))->identifier('class.extendsDeprecatedClass')->build();
					} else {
						$errors[] = RuleErrorBuilder::message(sprintf(
							"Class %s extends deprecated class %s:\n%s",
							$className,
							$parentClassName,
							$description,
						))->identifier('class.extendsDeprecatedClass')->build();
					}
				} else {
					if ($description === null) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Anonymous class extends deprecated class %s.',
							$parentClassName,
						))->identifier('class.extendsDeprecatedClass')->build();
					} else {
						$errors[] = RuleErrorBuilder::message(sprintf(
							"Anonymous class extends deprecated class %s:\n%s",
							$parentClassName,
							$description,
						))->identifier('class.extendsDeprecatedClass')->build();
					}
				}
			}
		} catch (ClassNotFoundException $e) {
			// Other rules will notify if the interface is not found
		}

		return $errors;
	}

}
