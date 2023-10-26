<?php

namespace Gems\SnippetsActions\Vue;

use Zalt\SnippetsActions\ParameterActionInterface;

class EditAction extends CreateAction  implements ParameterActionInterface
{
    /**
     * True when the form should edit a new model item.
     * @var boolean
     */
    public bool $createData = false;
}