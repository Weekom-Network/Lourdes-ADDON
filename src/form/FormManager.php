<?php

namespace form;

use form\types\CustomForm;
use form\types\ModalForm;
use form\types\SimpleForm;

/**
 * Class FormManager
 * @package form
 */
class FormManager
{

    /**
     * @param callable|null $function
     * @return CustomForm
     */
    public static function createCustomForm(?callable $function = null): CustomForm
    {
        return new CustomForm($function);
    }

    /**
     * @param callable|null $function
     * @return SimpleForm
     */
    public static function createSimpleForm(?callable $function = null): SimpleForm
    {
        return new SimpleForm($function);
    }

    /**
     * @param callable|null $function
     * @return ModalForm
     */
    public static function createModalForm(?callable $function = null): ModalForm
    {
        return new ModalForm($function);
    }
}