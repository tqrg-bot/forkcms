<?php

namespace Frontend\Modules\FormBuilder\Widgets;

use Common\Exception\RedirectException;
use Frontend\Core\Engine\Base\Widget as FrontendBaseWidget;
use Frontend\Core\Engine\Form as FrontendForm;
use Frontend\Core\Language\Language as FL;
use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Modules\FormBuilder\Engine\Model as FrontendFormBuilderModel;
use Frontend\Modules\FormBuilder\FormBuilderEvents;
use Frontend\Modules\FormBuilder\Event\FormBuilderSubmittedEvent;
use SpoonFormAttributes;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This is the form widget.
 */
class Form extends FrontendBaseWidget
{
    /**
     * Fields in HTML form.
     *
     * @var array
     */
    private $fieldsHTML;

    /**
     * The form.
     *
     * @var FrontendForm
     */
    private $frm;

    /**
     * Form name
     *
     * @var string
     */
    private $formName;

    /**
     * The form item.
     *
     * @var array
     */
    private $item;

    /**
     * Create form action and strip the identifier parameter.
     *
     * We use this function to create the action for the form.
     * This action cannot contain an identifier since these are used for
     * statistics and failed form submits cannot be tracked.
     *
     * @return string
     */
    private function createAction(): string
    {
        // pages
        $action = implode('/', $this->URL->getPages());

        // init parameters
        $parameters = $this->URL->getParameters();
        $moduleParameters = [];
        $getParameters = [];

        // sort by key (important for action order)
        ksort($parameters);

        // loop and filter parameters
        foreach ($parameters as $key => $value) {
            // skip identifier
            if ($key === 'identifier') {
                continue;
            }

            // normal parameter
            if (\SpoonFilter::isInteger($key)) {
                $moduleParameters[] = $value;
            } else {
                // get parameter
                $getParameters[$key] = $value;
            }
        }

        // single language
        if ($this->getContainer()->getParameter('site.multilanguage')) {
            $action = LANGUAGE . '/' . $action;
        }

        // add to action
        if (count($moduleParameters) > 0) {
            $action .= '/' . implode('/', $moduleParameters);
        }
        if (count($getParameters) > 0) {
            $action .= '?' . http_build_query($getParameters, null, '&', PHP_QUERY_RFC3986);
        }

        // remove trailing slash
        $action = rtrim($action, '/');

        // cough up action
        return '/' . $action;
    }

    public function execute(): void
    {
        parent::execute();

        $this->loadTemplate();
        $this->loadData();

        // success message
        if ($this->URL->hasParameter('identifier')
            && $this->URL->getParameter('identifier') == $this->item['identifier']
        ) {
            $this->parseSuccessMessage();
        } else {
            // create/handle form
            $this->buildForm();
            $this->validateForm();
            $this->parse();
        }
    }

    private function loadData(): void
    {
        // fetch the item
        $this->item = FrontendFormBuilderModel::get((int) $this->data['id']);

        // define form name
        $this->formName = 'form' . $this->item['id'];
    }

    private function buildForm(): void
    {
        // create form
        $this->frm = new FrontendForm('form' . $this->item['id']);

        // exists and has fields
        if (!empty($this->item) && !empty($this->item['fields'])) {
            // loop fields
            foreach ($this->item['fields'] as $field) {
                // init
                $item = [
                    'name' => 'field' . $field['id'],
                    'type' => $field['type'],
                    'label' => $field['settings']['label'] ?? '',
                    'placeholder' => $field['settings']['placeholder'] ?? null,
                    'classname' => $field['settings']['classname'] ?? null,
                    'required' => isset($field['validations']['required']),
                    'validations' => $field['validations'] ?? [],
                    'html' => '',
                ];

                // form values
                $values = $field['settings']['values'] ?? null;
                $defaultValues = $field['settings']['default_values'] ?? null;

                if ($field['type'] == 'dropdown') {
                    // values and labels are the same
                    $values = array_combine($values, $values);

                    // get index of selected item
                    $defaultIndex = array_search($defaultValues, $values, true);
                    if ($defaultIndex === false) {
                        $defaultIndex = null;
                    }

                    // create element
                    $ddm = $this->frm->addDropdown($item['name'], $values, $defaultIndex, false, $item['classname']);

                    // empty default element
                    $ddm->setDefaultElement('');

                    // add required attribute
                    if ($item['required']) {
                        $ddm->setAttribute('required', null);
                    }

                    $this->setCustomHTML5ErrorMessages($item, $ddm);
                    // get content
                    $item['html'] = $ddm->parse();
                } elseif ($field['type'] == 'radiobutton') {
                    // create element
                    $rbt = $this->frm->addRadiobutton($item['name'], $values, $defaultValues, $item['classname']);

                    // get content
                    $item['html'] = $rbt->parse();
                } elseif ($field['type'] == 'checkbox') {
                    // reset
                    $newValues = [];

                    // rebuild values
                    foreach ($values as $value) {
                        $newValues[] = ['label' => $value, 'value' => $value];
                    }

                    // create element
                    $chk = $this->frm->addMultiCheckbox($item['name'], $newValues, $defaultValues, $item['classname']);

                    // get content
                    $item['html'] = $chk->parse();
                } elseif ($field['type'] == 'textbox') {
                    // create element
                    $txt = $this->frm->addText($item['name'], $defaultValues, 255, $item['classname']);

                    // add required attribute
                    if ($item['required']) {
                        $txt->setAttribute('required', null);
                    }
                    if (isset($item['validations']['email'])) {
                        $txt->setAttribute('type', 'email');
                    }
                    if (isset($item['validations']['number'])) {
                        $txt->setAttribute('type', 'number');
                    }
                    if ($item['placeholder']) {
                        $txt->setAttribute('placeholder', $item['placeholder']);
                    }

                    $this->setCustomHTML5ErrorMessages($item, $txt);

                    // get content
                    $item['html'] = $txt->parse();
                } elseif ($field['type'] == 'datetime') {
                    // create element
                    if ($field['settings']['input_type'] == 'date') {
                        // calculate default value
                        $amount = $field['settings']['value_amount'];
                        $type = $field['settings']['value_type'];

                        if ($type != '') {
                            switch ($type) {
                                case 'today':
                                    $defaultValues = date('Y-m-d'); // HTML5 input needs this format
                                    break;
                                case 'day':
                                case 'week':
                                case 'month':
                                case 'year':
                                    if ($amount != '') {
                                        $defaultValues = date('Y-m-d', strtotime('+' . $amount . ' ' . $type));
                                    }
                                    break;
                            }
                        }

                        // Convert the php date format to a jquery date format
                        $dateFormatShortJS = FrontendFormBuilderModel::convertPHPDateToJquery($this->get('fork.settings')->get('Core', 'date_format_short'));

                        $datetime = $this->frm->addText($item['name'], $defaultValues, 255, 'inputDatefield ' . $item['classname'])->setAttributes(
                            [
                                'data-mask' => $dateFormatShortJS,
                                'data-firstday' => '1',
                                'type' => 'date',
                                'default-date' => (!empty($defaultValues) ? date($this->get('fork.settings')->get('Core', 'date_format_short'), strtotime($defaultValues)) : ''),
                            ]
                        );
                    } else {
                        $datetime = $this->frm->addText($item['name'], $defaultValues, 255, $item['classname'])->setAttributes(['type' => 'time']);
                    }

                    // add required attribute
                    if ($item['required']) {
                        $datetime->setAttribute('required', null);
                    }

                    $this->setCustomHTML5ErrorMessages($item, $datetime);

                    // get content
                    $item['html'] = $datetime->parse();
                } elseif ($field['type'] == 'textarea') {
                    // create element
                    $txt = $this->frm->addTextarea($item['name'], $defaultValues, $item['classname']);
                    $txt->setAttribute('cols', 30);

                    // add required attribute
                    if ($item['required']) {
                        $txt->setAttribute('required', null);
                    }
                    if ($item['placeholder']) {
                        $txt->setAttribute('placeholder', $item['placeholder']);
                    }

                    $this->setCustomHTML5ErrorMessages($item, $txt);

                    // get content
                    $item['html'] = $txt->parse();
                } elseif ($field['type'] == 'heading') {
                    $item['html'] = '<h3>' . $values . '</h3>';
                } elseif ($field['type'] == 'paragraph') {
                    $item['html'] = $values;
                } elseif ($field['type'] == 'submit') {
                    $item['html'] = $values;
                }

                // add to list
                $this->fieldsHTML[] = $item;
            }
        }
    }

    private function setCustomHTML5ErrorMessages(array $item, SpoonFormAttributes $formField): void
    {
        foreach ($item['validations'] as $validation) {
            $formField->setAttribute(
                'data-error-' . $validation['type'],
                $validation['error_message']
            );
        }
    }

    private function parse(): void
    {
        // form name
        $formName = 'form' . $this->item['id'];
        $this->tpl->assign('formName', $formName);
        $this->tpl->assign('formAction', $this->createAction() . '#' . $formName);
        $this->tpl->assign('successMessage', false);

        // got fields
        if (!empty($this->fieldsHTML)) {
            // value of the submit button
            $submitValue = '';

            // loop html fields
            foreach ($this->fieldsHTML as &$field) {
                if ($field['type'] == 'heading' || $field['type'] == 'paragraph') {
                    $field['plaintext'] = true;
                } elseif ($field['type'] == 'checkbox' || $field['type'] == 'radiobutton') {
                    // name (prefixed by type)
                    $name = ($field['type'] == 'checkbox') ?
                        'chk' . \SpoonFilter::toCamelCase($field['name']) :
                        'rbt' . \SpoonFilter::toCamelCase($field['name'])
                    ;

                    // rebuild so the html is stored in a general name (and not rbtName)
                    foreach ($field['html'] as &$item) {
                        $item['field'] = $item[$name];
                    }

                    // multiple items
                    $field['multiple'] = true;
                } elseif ($field['type'] == 'submit') {
                    $submitValue = $field['html'];
                } else {
                    $field['simple'] = true;
                }

                // errors (only for form elements)
                if (isset($field['simple']) || isset($field['multiple'])) {
                    $field['error'] = $this->frm->getField(
                        $field['name']
                    )->getErrors();
                }
            }

            // assign
            $this->tpl->assign('submitValue', $submitValue);
            $this->tpl->assign('fields', $this->fieldsHTML);

            // parse form
            $this->frm->parse($this->tpl);
            $this->tpl->assign('formToken', $this->frm->getToken());

            // assign form error
            $this->tpl->assign('error', ($this->frm->getErrors() != '' ? $this->frm->getErrors() : false));
        }
    }

    private function parseSuccessMessage(): void
    {
        // form name
        $this->tpl->assign('formName', $this->formName);
        $this->tpl->assign('successMessage', $this->item['success_message']);
    }

    private function validateForm(): void
    {
        // submitted
        if ($this->frm->isSubmitted()) {
            // does the key exists?
            if (\SpoonSession::exists('formbuilder_' . $this->item['id'])) {
                // calculate difference
                $diff = time() - (int) \SpoonSession::get('formbuilder_' . $this->item['id']);

                // calculate difference, it it isn't 10 seconds the we tell the user to slow down
                if ($diff < 10 && $diff != 0) {
                    $this->frm->addError(FL::err('FormTimeout'));
                }
            }

            // validate fields
            foreach ($this->item['fields'] as $field) {
                // field name
                $fieldName = 'field' . $field['id'];

                // skip
                if ($field['type'] == 'submit' || $field['type'] == 'paragraph' || $field['type'] == 'heading') {
                    continue;
                }

                // loop other validations
                foreach ($field['validations'] as $rule => $settings) {
                    // already has an error so skip
                    if ($this->frm->getField($fieldName)->getErrors() !== null) {
                        continue;
                    }

                    // required
                    if ($rule == 'required') {
                        $this->frm->getField($fieldName)->isFilled($settings['error_message']);
                    } elseif ($rule == 'email') {
                        // only check this if the field is filled, if the field is required it will be validated before
                        if ($this->frm->getField($fieldName)->isFilled()) {
                            $this->frm->getField($fieldName)->isEmail(
                                $settings['error_message']
                            );
                        }
                    } elseif ($rule == 'number') {
                        // only check this if the field is filled, if the field is required it will be validated before
                        if ($this->frm->getField($fieldName)->isFilled()) {
                            $this->frm->getField($fieldName)->isNumeric(
                                $settings['error_message']
                            );
                        }
                    } elseif ($rule == 'time') {
                        $regexTime = '/^(([0-1][0-9]|2[0-3]|[0-9])|([0-1][0-9]|2[0-3]|[0-9])(:|h)[0-5]?[0-9]?)$/';
                        if (!\SpoonFilter::isValidAgainstRegexp($regexTime, $this->frm->getField($fieldName)->getValue())) {
                            $this->frm->getField($fieldName)->setError($settings['error_message']);
                        }
                    }
                }
            }

            // valid form
            if ($this->frm->isCorrect()) {
                // item
                $data = [
                    'form_id' => $this->item['id'],
                    'session_id' => \SpoonSession::getSessionId(),
                    'sent_on' => FrontendModel::getUTCDate(),
                    'data' => serialize(['server' => $_SERVER]),
                ];

                $dataId = null;
                // insert data
                if ($this->item['method'] !== 'email') {
                    $dataId = FrontendFormBuilderModel::insertData($data);
                }

                // init fields array
                $fields = [];

                // loop all fields
                foreach ($this->item['fields'] as $field) {
                    // skip
                    if ($field['type'] == 'submit' || $field['type'] == 'paragraph' || $field['type'] == 'heading') {
                        continue;
                    }

                    // field data
                    $fieldData = [];
                    $fieldData['data_id'] = $dataId;
                    $fieldData['label'] = $field['settings']['label'];
                    $fieldData['value'] = $this->frm->getField('field' . $field['id'])->getValue();

                    if ($field['type'] == 'radiobutton') {
                        $values = [];

                        foreach ($field['settings']['values'] as $value) {
                            $values[$value['value']] = $value['label'];
                        }

                        $fieldData['value'] = array_key_exists($fieldData['value'], $values)
                            ? $values[$fieldData['value']] : null;
                    }

                    // clean up
                    if (is_array($fieldData['value']) && empty($fieldData['value'])) {
                        $fieldData['value'] = null;
                    }

                    // serialize
                    if ($fieldData['value'] !== null) {
                        $fieldData['value'] = serialize($fieldData['value']);
                    }

                    // save fields data
                    $fields[$field['id']] = $fieldData;

                    // insert
                    if ($this->item['method'] !== 'email') {
                        FrontendFormBuilderModel::insertDataField($fieldData);
                    }
                }

                $this->get('event_dispatcher')->dispatch(
                    FormBuilderEvents::FORM_SUBMITTED,
                    new FormBuilderSubmittedEvent($this->item, $fields, $dataId)
                );

                // store timestamp in session so we can block excessive usage
                \SpoonSession::set('formbuilder_' . $this->item['id'], time());

                // redirect
                $redirect = SITE_URL . $this->URL->getQueryString();
                $redirect .= (stripos($redirect, '?') === false) ? '?' : '&';
                $redirect .= 'identifier=' . $this->item['identifier'];
                $redirect .= '#' . $this->formName;

                throw new RedirectException(
                    'Redirect',
                    new RedirectResponse($redirect)
                );
            } else {
                // not correct, show errors
                // global form errors set
                if ($this->frm->getErrors() != '') {
                    $this->tpl->assign('formBuilderError', $this->frm->getErrors());
                } else {
                    // general error
                    $this->tpl->assign('formBuilderError', FL::err('FormError'));
                }
            }
        }
    }
}
