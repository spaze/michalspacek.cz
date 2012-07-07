<?php
/**
 * Base class for all application presenters.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{


	public function beforeRender()
	{
		$this->template->debugMode = $this->context->parameters['debugMode'];
	}


	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(callback(new \Bare\Nette\Templating\Helpers($this->getContext()), 'loader'));
		return $template;
	}


}
