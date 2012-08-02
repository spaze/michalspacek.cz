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
		$parameters = $this->getContext()->getParameters();
		$this->template->debugMode = (isset($parameters['debugMode']) ? $parameters['debugMode'] : false);
	}


	protected function createTemplate($class = null)
	{
		$template = parent::createTemplate($class);
		$template->registerHelperLoader(callback(new \Bare\Nette\Templating\Helpers($this->getContext()), 'loader'));
		return $template;
	}


}
