<?php

namespace Frontend\Core\Engine;

use Common\Core\Twig\BaseTwigTemplate;
use Common\Core\Twig\Extensions\TwigFilters;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bridge\Twig\Extension\FormExtension as SymfonyFormExtension;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Twig_Environment;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is a twig template wrapper
 * that glues spoon libraries and code standards with twig
 */
class TwigTemplate extends BaseTwigTemplate
{
    /**
     * @var string
     */
    private $themePath;

    public function __construct(
        Twig_Environment $environment,
        TemplateNameParserInterface $parser,
        FileLocatorInterface $locator
    ) {
        parent::__construct($environment, $parser, $locator);

        $container = Model::getContainer();
        $this->debugMode = $container->getParameter('kernel.debug');
        $this->environment->disableStrictVariables();
        new FormExtension($this->environment);
        TwigFilters::addFilters($this->environment, 'Frontend');
        $this->startGlobals($this->environment);

        if (!$container->getParameter('fork.is_installed')) {
            return;
        }

        $this->addFrontendPathsToTheTemplateLoader($container->get('fork.settings')->get('Core', 'theme', 'default'));
        $this->connectSymfonyForms();
    }

    private function addFrontendPathsToTHeTemplateLoader(string $theme): void
    {
        $this->themePath = FRONTEND_PATH . '/Themes/' . $theme;
        $this->environment->setLoader(
            new \Twig_Loader_Chain(
                [$this->environment->getLoader(), new \Twig_Loader_Filesystem($this->getLoadingFolders())]
            )
        );
    }

    private function connectSymfonyForms(): void
    {
        $formEngine = new TwigRendererEngine($this->getFormTemplates('FormLayout.html.twig'));
        $formEngine->setEnvironment($this->environment);
        $this->environment->addExtension(
            new SymfonyFormExtension(
                new TwigRenderer($formEngine, Model::get('security.csrf.token_manager'))
            )
        );
    }

    /**
     * Convert a filename extension
     *
     * @param string $template
     *
     * @return string
     */
    public function getPath(string $template): string
    {
        if (strpos($template, FRONTEND_MODULES_PATH) !== false) {
            return str_replace(FRONTEND_MODULES_PATH . '/', '', $template);
        }

        // else it's in the theme folder
        return str_replace($this->themePath . '/', '', $template);
    }

    /**
     * Adds a global variable to the template
     *
     * @param string $name
     * @param mixed $value
     */
    public function addGlobal(string $name, $value): void
    {
        $this->environment->addGlobal($name, $value);
    }

    /**
     * Fetch the parsed content from this template.
     *
     * @param string $template The location of the template file, used to display this template.
     *
     * @return string The actual parsed content after executing this template.
     */
    public function getContent(string $template): string
    {
        $template = $this->getPath($template);

        $content = $this->render(
            $template,
            $this->variables
        );

        $this->variables = [];

        return $content;
    }

    public function render($template, array $variables = []): string
    {
        if (!empty($this->forms)) {
            foreach ($this->forms as $form) {
                // using assign to pass the form as global
                $this->environment->addGlobal('form_' . $form->getName(), $form);
            }
        }

        return $this->environment->render($template, $variables);
    }

    private function getLoadingFolders(): array
    {
        return $this->filterOutNonExistingPaths(
            [
                $this->themePath . '/Modules',
                $this->themePath,
                FRONTEND_MODULES_PATH,
                FRONTEND_PATH,
            ]
        );
    }

    private function getFormTemplates(string $fileName): array
    {
        return $this->filterOutNonExistingPaths(
            [
                FRONTEND_PATH . '/Core/Layout/Templates/' . $fileName,
                $this->themePath . '/Core/Layout/Templates/' . $fileName,
            ]
        );
    }

    private function filterOutNonExistingPaths(array $files): array
    {
        $filesystem = new Filesystem();

        return array_filter(
            $files,
            function ($folder) use ($filesystem) {
                return $filesystem->exists($folder);
            }
        );
    }
}
