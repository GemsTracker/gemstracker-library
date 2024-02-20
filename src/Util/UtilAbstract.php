<?php

/**
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Cache\HelperAdapter;
use Gems\Util\UtilDbHelper;
use MUtil\Registry\TargetAbstract;
use Zalt\Base\TranslateableTrait;

/**
 * Abstract utility class containing caching and sql loading function
 *
 * @package    Gems
 * @subpackage UtilAbstract
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 24-sep-2015 11:37:10
 */
class UtilAbstract extends TargetAbstract
{
    use \MUtil\Translate\TranslateableTrait;

    /**
     *
     * @var HelperAdapter
     */
    protected $cache;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \MUtil\Registry\Source
     */
    protected $source;

    public function __construct(
        protected UtilDbHelper $utilDbHelper,
    ) {}

    /**
     * Returns a callable if a method is called as a variable
     *
     * @param string $name
     * @return \MUtil\Lazy\Call
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return \MUtil\Lazy::call(array($this, $name));
        }

        throw new \Gems\Exception\Coding("Unknown method '$name' requested as callable.");
    }
}
