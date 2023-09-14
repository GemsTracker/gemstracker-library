<?php

namespace Gems\SnippetsActions\Vue;

class EditAction extends CreateAction
{
    /**
     * True when the form should edit a new model item.
     * @var boolean
     */
    public bool $createData = false;
}