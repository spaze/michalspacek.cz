<?php
namespace Companies20Module;

/**
 * Base class for all companies20 module presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{


	protected function startup()
	{
		parent::startup();

		$startup = $this->getContext()->getByType(\MichalSpacekCz\Startup::class);
		$startup->startup();
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}


}
