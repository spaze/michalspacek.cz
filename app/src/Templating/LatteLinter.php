<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Latte\Feature;
use Latte\Tools\Linter;
use Latte\Tools\LinterExtension;
use MichalSpacekCz\Application\Cli\CliArgs;
use MichalSpacekCz\Application\Cli\CliArgsProvider;
use Nette\CommandLine\Command;
use Nette\CommandLine\Console;
use Nette\CommandLine\HelpRenderer;
use Nette\CommandLine\Normalizers;
use Override;

final readonly class LatteLinter implements CliArgsProvider
{

	private const string ARG_PATH = 'path';
	private const string ARG_DISABLE_STRICT_PARSING = '--disable-strict-parsing';


	public function __construct(
		private TemplateFactory $templateFactory,
		private CliArgs $cliArgs,
		private Console $console,
	) {
	}


	/**
	 * Almost the same as vendor/latte/latte/bin/latte-lint, but extended
	 * to support custom filters by passing a configured engine to the Linter.
	 *
	 * @see https://github.com/nette/latte/issues/286
	 * @return int The exit status: 0 every template compiles, 1 some do not, 2 bad arguments
	 */
	public function scan(): int
	{
		$this->echo('Latte linter');
		$this->echo('------------');
		$customFilters = $this->templateFactory->getCustomFilters();
		$this->echo('Custom filters: ' . ($customFilters !== [] ? implode(', ', $customFilters) : 'none installed'));

		$cliArgsError = $this->cliArgs->getError();
		if ($cliArgsError !== null) {
			// The help drawn from the definition, so it never says anything else than what the script accepts; through the console, so a terminal
			// gets it in color, though not with --colors, which a command line that failed to parse never gave
			$renderer = new HelpRenderer($this->console);
			$this->echo($renderer->formatText($cliArgsError->getMessage()));
			echo "\n" . $renderer->renderToString($cliArgsError->command);
			return 2;
		}
		$debug = $this->cliArgs->getFlag(CliArgs::DEBUG); // the flag every script has doubles as the linter's own debug switch
		if ($debug) {
			$this->echo('Debug mode enabled');
		}
		$strictParsing = !$this->cliArgs->getFlag(self::ARG_DISABLE_STRICT_PARSING);
		if ($strictParsing) {
			$this->echo('Strict parsing mode enabled');
		}

		$latteEngine = $this->templateFactory->createTemplate()->getLatte();
		$latteEngine->setFeature(Feature::StrictParsing, $strictParsing);
		$latteEngine->addExtension(new LinterExtension());
		// Nette\Bridges\ApplicationLatte\UIExtension only exposes these two when it has a control with a presenter,
		// and the engine here has neither, so every use of them would be reported as an unknown function. The linter
		// compiles templates and never runs them, so what these return doesn't matter, only that the names exist.
		$latteEngine->addFunction('isLinkCurrent', static fn(): bool => true);
		$latteEngine->addFunction('isModuleCurrent', static fn(): bool => true);
		$linter = new Linter($latteEngine, $debug);
		return $linter->scanDirectory($this->cliArgs->getArg(self::ARG_PATH)) ? 0 : 1;
	}


	private function echo(string $message): void
	{
		echo "{$message}\n";
	}


	#[Override]
	public static function getDescription(): string
	{
		return 'Compiles every Latte template under the directory with the filters and functions of the application installed, so a template using them is checked as well';
	}


	#[Override]
	public static function defineArgs(Command $command): void
	{
		$command->addFlag(self::ARG_DISABLE_STRICT_PARSING, 'Let unknown Latte tags pass as HTML instead of failing the lint');
		$command->addArgument(self::ARG_PATH, 'The directory with the templates to lint', normalizer: Normalizers::realPath(...));
	}

}
