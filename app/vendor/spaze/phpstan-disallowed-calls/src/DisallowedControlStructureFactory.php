<?php
declare(strict_types = 1);

namespace Spaze\PHPStan\Rules\Disallowed;

use PHPStan\ShouldNotHappenException;
use Spaze\PHPStan\Rules\Disallowed\Allowed\AllowedConfigFactory;
use Spaze\PHPStan\Rules\Disallowed\Exceptions\UnsupportedParamTypeInConfigException;
use Spaze\PHPStan\Rules\Disallowed\Formatter\Formatter;

class DisallowedControlStructureFactory
{

	/**
	 * @see https://www.php.net/language.control-structures
	 */
	private const CONTROL_STRUCTURES = [
		'if',
		'else',
		'elseif',
		'while',
		'do-while',
		'for',
		'foreach',
		'break',
		'continue',
		'switch',
		'match',
		'declare',
		'return',
		'require',
		'include',
		'require_once',
		'include_once',
		'goto',
	];

	private Formatter $formatter;

	private AllowedConfigFactory $allowedConfigFactory;


	public function __construct(Formatter $formatter, AllowedConfigFactory $allowedConfigFactory)
	{
		$this->formatter = $formatter;
		$this->allowedConfigFactory = $allowedConfigFactory;
	}


	/**
	 * @param array<array{controlStructure?:string|list<string>, structure?:string|list<string>, message?:string, allowIn?:list<string>, allowExceptIn?:list<string>, disallowIn?:list<string>, errorIdentifier?:string, errorTip?:string}> $config
	 * @return list<DisallowedControlStructure>
	 * @throws ShouldNotHappenException
	 */
	public function getDisallowedControlStructures(array $config): array
	{
		$disallowedControlStructures = [];
		foreach ($config as $disallowed) {
			$controlStructures = $disallowed['controlStructure'] ?? $disallowed['structure'] ?? null;
			unset($disallowed['controlStructure'], $disallowed['structure']);
			if (!$controlStructures) {
				throw new ShouldNotHappenException("Either 'controlStructure' or 'structure' must be set in configuration items");
			}
			$controlStructures = (array)$controlStructures;
			try {
				foreach ($controlStructures as $controlStructure) {
					if ($controlStructure === 'else if') {
						throw new ShouldNotHappenException("Use 'elseif' instead of 'else if', because 'else if' is parsed as 'else' followed by 'if' and the behaviour may be unexpected if using 'else if' in the configuration");
					}
					if (!in_array($controlStructure, self::CONTROL_STRUCTURES, true)) {
						throw new ShouldNotHappenException(sprintf('%s is not a supported control structure, use one of %s', $controlStructure, implode(', ', self::CONTROL_STRUCTURES)));
					}
					$disallowedControlStructure = new DisallowedControlStructure(
						$controlStructure,
						$disallowed['message'] ?? null,
						$this->allowedConfigFactory->getConfig($disallowed),
						$disallowed['errorIdentifier'] ?? null,
						$disallowed['errorTip'] ?? null
					);
					$disallowedControlStructures[$disallowedControlStructure->getControlStructure()] = $disallowedControlStructure;
				}
			} catch (UnsupportedParamTypeInConfigException $e) {
				throw new ShouldNotHappenException(sprintf('%s: %s', $this->formatter->formatIdentifier($controlStructures), $e->getMessage()));
			}
		}
		return array_values($disallowedControlStructures);
	}

}
