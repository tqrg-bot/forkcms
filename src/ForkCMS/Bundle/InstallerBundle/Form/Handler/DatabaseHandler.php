<?php

namespace ForkCMS\Bundle\InstallerBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Validates and saves the data from the databases form
 */
class DatabaseHandler
{
    public function process(Form $form, Request $request): bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        $form->handleRequest($request);

        if ($form->isValid()) {
            return $this->processValidForm($form, $request);
        }

        return false;
    }

    public function processValidForm(Form $form, Request $request): bool
    {
        $request->getSession()->set('installation_data', $form->getData());

        return true;
    }
}
