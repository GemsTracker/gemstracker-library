<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Repository\TokenRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\TableElement;
use Zalt\Late\Late;
use Zalt\Late\RepeatableByKeyValue;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets_Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class TokenStatusLegenda extends \Zalt\Snippets\TranslatableSnippetAbstract
{
    public function __construct(
        SnippetOptions $snippetOptions, 
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        protected TokenRepository $tokenRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate);
    }

    public function getHtmlOutput()
    {
        $repeater = new RepeatableByKeyValue($this->tokenRepository->getEveryStatus());
        $table    = new TableElement();
        $table->class = 'compliance timeTable rightFloat table table-condensed';
        $table->setRepeater($repeater);

        $table->throw($this->_('Legend'));
        $cell = $table->td();
        $cell->class = array(
            'round',
            Late::method($this->tokenRepository, 'getStatusClass', $repeater->key)
            );
        $cell->append(Late::method($this->tokenRepository, 'getStatusIcon', $repeater->key));
        $table->td($repeater->value);

        return $table;
    }
}
