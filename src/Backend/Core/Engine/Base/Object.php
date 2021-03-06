<?php

namespace Backend\Core\Engine\Base;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use ForkCMS\App\KernelLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Backend\Core\Engine\Authentication;
use Backend\Core\Engine\Exception;
use Common\Exception\RedirectException;

/**
 * This class will be the base of the objects used in the cms
 */
class Object extends KernelLoader
{
    /**
     * The current action
     *
     * @var string
     */
    protected $action;

    /**
     * The actual output
     *
     * @var mixed
     */
    protected $content;

    /**
     * The current module
     *
     * @var string
     */
    protected $module;

    public function getAction(): string
    {
        return $this->action;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setAction(string $action, string $module = null): void
    {
        // set module
        if ($module !== null) {
            $this->setModule($module);
        }

        // check if module is set
        if ($this->getModule() === null) {
            throw new Exception('Module has not yet been set.');
        }

        // is this action allowed?
        if (!Authentication::isAllowedAction($action, $this->getModule())) {
            // set correct headers
            header('HTTP/1.1 403 Forbidden');

            // throw exception
            throw new Exception('Action not allowed.');
        }

        // set property
        $this->action = \SpoonFilter::toCamelCase($action);
    }

    public function setModule(string $module): void
    {
        // is this module allowed?
        if (!Authentication::isAllowedModule($module)) {
            // set correct headers
            header('HTTP/1.1 403 Forbidden');

            // throw exception
            throw new Exception('Module not allowed.');
        }

        // set property
        $this->module = $module;
    }

    /**
     * Since the display action in the backend is rather complicated and we
     * want to make this work with our Kernel, I've added this getContent
     * method to extract the output from the actual displaying.
     *
     * With this function we'll be able to get the content and return it as a
     * Symfony output object.
     *
     * @return Response
     */
    public function getContent(): Response
    {
        return new Response(
            $this->content,
            200
        );
    }

    /**
     * Redirect to a given URL
     *
     * @param string $url The URL to redirect to.
     * @param int $code The redirect code, default is 302 which means this is a temporary redirect.
     *
     * @throws RedirectException
     */
    public function redirect(string $url, int $code = Response::HTTP_FOUND): void
    {
        throw new RedirectException('Redirect', new RedirectResponse($url, $code));
    }

    protected function redirectToErrorPage(string $type, int $code = Response::HTTP_BAD_REQUEST): void
    {
        $errorUrl = '/' . NAMED_APPLICATION . '/' . $this->get('request')->getLocale() . '/error?type=' . $type;

        $this->redirect($errorUrl, $code);
    }

    /**
     * Load the config file for the requested module.
     * In the config file we have to find disabled actions, the constructor
     * will read the folder and set possible actions
     * Other configurations will be stored in it also.
     */
    public function getConfig(): Config
    {
        // check if we can load the config file
        $configClass = 'Backend\\Modules\\' . $this->getModule() . '\\Config';
        if ($this->getModule() === 'Core') {
            $configClass = Config::class;
        }

        // validate if class exists (aka has correct name)
        if (!class_exists($configClass)) {
            throw new Exception('The config file ' . $configClass . ' could not be found.');
        }

        // create config-object, the constructor will do some magic
        return new $configClass($this->getKernel(), $this->getModule());
    }
}
