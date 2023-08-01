<?php

/**
 * @package    Gems
 * @subpackage Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2020 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

use Gems\Tracker\Source\LimeSurvey3m00Database as LSSingleSource;
use Gems\Tracker\Token;
use Gems\User\Organization;
use Laminas\Db\Sql\Select;
use MUtil\Model;

/**
 * This source allows to share one LimeSurvey with multiple satellite GemsTracker installations.
 * 
 * To assure unique tokens, the GemsTracker created tokens will be prefixed with 
 * the GEMS_PROJECT_NAME when inserted into LimeSurvey. When returning to GemsTracker
 * this prefix will be stripped from the return URI again so GemsTracker knows
 * what token returned.
 * 
 * The _getReturnURI allows the remote LimeSurvey to redirect to the correct site. 
 * You can make use of the Expression Manager and available attributes to create
 * the correct URI.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2020 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class LimeSurvey3m00MultiSource extends LSSingleSource {

    /**
     * A map containing attributename => databasefieldname mappings
     *
     * Should contain maps for respondentid, organizationid and consentcode.
     *
     * @var array<string, string>
     */
    protected array $_attributeMap = [
        'respondentid'   => 'attribute_1',
        'organizationid' => 'attribute_2',
        'consentcode'    => 'attribute_3',
        'resptrackid'    => 'attribute_4',
        'site'           => 'attribute_5',
        'sitename'       => 'attribute_6',
    ];

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     * 
     * Adding site (GEMS_PROJECT_NAME) and sitename
     *
     * @param \Gems\Tracker\Token $token
     * @return string[] Of fieldname => value type
     */
    protected function _fillAttributeMap(Token $token): array
    {
        $values = parent::_fillAttributeMap($token);

        $values[$this->_attributeMap['site']]     = $this->_getSite();

        $projectName = '';
        if (isset($this->config['app']['name'])) {
            $projectName = $this->config['app']['name'];
        }

        $values[$this->_attributeMap['sitename']] = substr($projectName, 0, $this->attributeSize);

        return $values;
    }

    /**
     * Reads the site part from the URL
     * 
     * The last part before the / will be the site name to use. Most of the times
     * this equals the lowercase projectname, but as with all rules there are
     * exceptions.
     * 
     * @return string
     */
    protected function _getSite(): string
    {
        $siteUri   = $this->currentUrlRepository->getCurrentUrl();
        $siteParts = explode('/', $siteUri);
        return array_pop($siteParts);
    }

    /**
     * Adds or removes the site prefix (GEMS_PROJECT_NAME) that makes the tokens unique.
     * 
     * @param string $tokenId
     * @param bool $reverse
     * @return string
     */
    protected function _getToken(string $tokenId, bool $reverse = false): string
    {
        $newTokenId = parent::_getToken($tokenId, $reverse);

        if ($reverse) {
            return substr($newTokenId, strlen($this->_getSite()));
        } else {
            return $this->_getSite() . $newTokenId;
        }
    }

    /**
     * Add the site to the filter so we only get our own site's responses.
     * 
     * @param array $filter
     * @param int $surveyId
     * @param int $sourceSurveyId
     */
    public function getRawTokenAnswerRowsSelect(array $filter, int $surveyId, int|string|null $sourceSurveyId = null): Select
    {
        // Add the extra site attribute
        $filter['site'] = $this->_getSite();
        return parent::getRawTokenAnswerRowsSelect($filter, $surveyId, $sourceSurveyId);
    }

    /**
     * Creates the right URI that LimeSurvey should return the users to after completion.
     * 
     * Makes use of the site (GEMS_PROJECT_NAME) to find the correct URL and strip 
     * the prefix of the token using Expression Manager.
     * 
     * @return string
     */
    protected function _getReturnURI(Organization|null $organization = null): string
    {
        return substr($this->currentUrlRepository->getCurrentUrl(), 0, -strlen($this->_getSite())) . '{TOKEN:ATTRIBUTE_5}/ask/return/' . Model::REQUEST_ID . '/{substr(TOKEN,strlen(TOKEN:ATTRIBUTE_5))}';
    }

    /**
     * Set the return URI description to include the sitename from the token attribute instead of the hardcoded one in the original source.
     * 
     * @return string
     */
    protected function _getReturnURIDescription(string $language): string
    {
        return sprintf(
                $this->translate->_('Back to %s', [], null, $language),
                //$this->project->getName()
                '{TOKEN:ATTRIBUTE_6}'
        );
    }
}
