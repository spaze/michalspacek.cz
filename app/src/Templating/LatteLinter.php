<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Latte\Tools\Linter;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use Nette\CommandLine\Parser;
use Override;

final readonly class LatteLinter implements CliArgsProvider
{

	private const string ARG_PATH = 'path';
	private const string ARG_DEBUG = '--debug';
	private const string ARG_DISABLE_STRICT_PARSING = '--disable-strict-parsing';


	public function __construct(
		private TemplateFactory $templateFactory,
		private CliArgs $cliArgs,
	) {
	}


	/**
	 * Almost the same as vendor/latte/latte/bin/latte-lint, but extended
	 * to support custom filters by passing a configured engine to the Linter.
	 *
	 * @see https://github.com/nette/latte/issues/286
	 */
	public function scan(): never
	{
		$this->echo('Latte linter');
		$this->echo('------------');
		$customFilters = $this->templateFactory->getCustomFilters();
		$this->echo('Custom filters: ' . ($customFilters !== [] ? implode(', ', $customFilters) : 'none installed'));

		$cliArgsError = $this->cliArgs->getError();
		if ($cliArgsError !== null) {
			$this->echo(sprintf('Usage: latte-lint <%s> [%s] [%s]', self::ARG_PATH, self::ARG_DEBUG, self::ARG_DISABLE_STRICT_PARSING));
			$this->echo($cliArgsError);
			exit(2);
		}
		$debug = $this->cliArgs->getFlag(self::ARG_DEBUG);
		if ($debug) {
			$this->echo('Debug mode enabled');
		}
		$strictParsing = !$this->cliArgs->getFlag(self::ARG_DISABLE_STRICT_PARSING);
		if ($strictParsing) {
			$this->echo('Strict parsing mode enabled');
		}

		$latteEngine = $this->templateFactory->createTemplate()->getLatte();
		$latteEngine->setStrictParsing($strictParsing);
		$linter = new Linter($latteEngine, $debug);
		$ok = $linter->scanDirectory($this->cliArgs->getArg(self::ARG_PATH));
		exit($ok ? 0 : 1);
	}


	private function echo(string $message): void
	{
		echo "{$message}\n";
	}


	#[Override]
	public static function getArgs(): array
	{
		return [
			self::ARG_DEBUG,
			self::ARG_DISABLE_STRICT_PARSING,
		];
	}


	#[Override]
	public static function getPositionalArgs(): array
	{
		return [
			self::ARG_PATH => [Parser::RealPath => true],
		];
	}

}
