<?php

namespace Frontend\Core\Engine\Base;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use ForkCMS\App\KernelLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * This class implements a lot of functionality that can be extended by a specific AJAX action
 */
class AjaxAction extends KernelLoader
{
    public const OK = 200;
    public const BAD_REQUEST = 400;
    public const FORBIDDEN = 403;
    public const ERROR = 500;

    /**
     * The current action
     *
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $content;

    /**
     * The current module
     *
     * @var string
     */
    protected $module;

    public function __construct(KernelInterface $kernel, string $action, string $module)
    {
        parent::__construct($kernel);

        // store the current module and action (we grab them from the URL)
        $this->setModule($module);
        $this->setAction($action);
    }

    public function execute(): void
    {
        // placeholder
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Since the display action in the frontend is rather complicated and we
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
            json_encode($this->content),
            $this->content['code'] ?? self::OK,
            ['content-type' => 'application/json']
        );
    }

    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Outputs an answer to the browser
     *
     * @param int $statusCode The status code to use, use one of the available constants
     *                           (self::OK, self::BAD_REQUEST, self::FORBIDDEN, self::ERROR).
     * @param mixed $data The data to be returned (will be encoded as JSON).
     * @param string $message A text-message.
     */
    public function output(int $statusCode, $data = null, string $message = null): void
    {
        $this->content = ['code' => $statusCode, 'data' => $data, 'message' => $message];
    }

    protected function setAction(string $action): void
    {
        $this->action = $action;
    }

    protected function setModule(string $module): void
    {
        $this->module = $module;
    }
}
