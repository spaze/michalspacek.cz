<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Cli;

use InvalidArgumentException;
use LogicException;
use Nette\CommandLine\Command;
use Nette\CommandLine\Parameters\Flag;
use Nette\CommandLine\Parameters\Parameter;
use Nette\CommandLine\Parameters\ValueParameter;
use Nette\CommandLine\ParseException;
use Nette\CommandLine\Parser;
use Nette\CommandLine\ParseResult;

final readonly class CliArgs
{

	/** Every script takes these, on top of what its provider defines */
	public const string HELP = '--help';
	public const string DEBUG = '--debug';
	public const string COLORS = '--colors';


	private function __construct(
		private ParseResult $result,
	) {
	}


	/**
	 * @param class-string<CliArgsProvider> $provider
	 * @param list<string>|null $argv The command line without the script name, null means the running process's, which the parser reads itself
	 * @throws ParseException The command line is wrong, which bootCli() reports; a mistake in the definition is an InvalidArgumentException instead, a bug that stops the boot right there
	 */
	public static function parse(string $provider, ?array $argv = null): self
	{
		$command = new Command(description: $provider::getDescription());
		// Defined before the provider's own, so a provider that defines one of them again fails in its own defineArgs() frame.
		// Standalone, --help answers even when the required arguments are missing: nothing else is demanded or converted then
		$command->addFlag(self::HELP, 'Print this help', alias: '-h', standalone: true);
		$command->addFlag(self::DEBUG, 'Boot with the development config and Tracy in development mode');
		$command->addFlag(self::COLORS, 'Color the output even when it is not a terminal');
		$provider::defineArgs($command);
		return new self((new Parser())->parse($command, $argv));
	}


	/**
	 * @return bool Whether the flag was given on the command line
	 * @throws InvalidArgumentException The provider defined no such flag, or defined the name as another kind
	 */
	public function getFlag(string $name): bool
	{
		return $this->getValue($name, Flag::class) === true;
	}


	/**
	 * @return string The value of the argument, or of the option
	 * @throws InvalidArgumentException The provider defined no such argument or option, or defined the name as another kind
	 * @throws LogicException The value is not a string, which an optional argument or option that was not given is
	 */
	public function getArg(string $name): string
	{
		$value = $this->getValue($name, ValueParameter::class);
		if (!is_string($value)) {
			throw new LogicException("Parameter {$name} is not a string");
		}
		return $value;
	}


	/**
	 * @return Command The definition the command line was read against, for `HelpRenderer` to draw the help from
	 */
	public function getCommand(): Command
	{
		return $this->result->command;
	}


	/**
	 * The definition refuses a name nobody defined, and a flag read as an argument or the other way round, which would
	 * otherwise quietly read as not given
	 *
	 * @template T of Parameter
	 * @param class-string<T> $kind
	 */
	private function getValue(string $name, string $kind): mixed
	{
		$this->result->command->getParameter($name, $kind);
		return $this->result->get($name);
	}

}
