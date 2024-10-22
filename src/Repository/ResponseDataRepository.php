<?php

namespace Gems\Repository;

use Gems\Db\ResponseDbAdapter;
use Gems\Db\ResultFetcher;
use Gems\Helper\Env;
use Gems\Tracker;
use Gems\Tracker\Source\SourceAbstract;
use Gems\Tracker\Survey;
use Laminas\Db\Metadata\Source\Factory;
use Symfony\Component\String\UnicodeString;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;

class ResponseDataRepository
{
    protected string|null $gemsDbName = null;

    protected ResultFetcher $resultFetcher;

    protected string $responseTable = 'gemsdata__responses';

    protected string|null $responseDbName = null;

    public function __construct(
        protected readonly ResponseDbAdapter $responseDbAdapter,
        protected readonly Tracker $tracker,
        array $config,
    )
    {
        $this->resultFetcher = new ResultFetcher($this->responseDbAdapter);

        $this->gemsDbName = Env::get('DB_NAME', $this->config['db']['database'] ?? null);
        $this->responseDbName = Env::get('RESPONSE_DB_NAME', $config['responseData']['database'] ?? Env::get('DB_NAME', $config['db']['database'] ?? null));
    }

    public function addResponses(string $tokenId, array $responses, int $userId)
    {
        $defaultData = $this->getDefaultRowData($tokenId, $userId);
        //$responses = $this->removeMetaFields($tokenId, $responses);

        $currentResponses = $this->getCurrentResponses($tokenId);
        $inserts = [];

        $checkDuplicateNames = [];

        foreach($responses as $fieldName => $response) {
            $data = $defaultData;
            $data['gdr_answer_id'] = $fieldName;
            if (is_array($response)) {
                $response = join('|', $response);
            }
            $stringObject = new UnicodeString($response);
            $response = $stringObject->normalize()->toString();

            $data['gdr_response'] = $response;

            if (in_array(strtolower($fieldName), $checkDuplicateNames)) {
                continue;
            }
            $checkDuplicateNames[] = strtolower($fieldName);

            if (array_key_exists($fieldName, $currentResponses)) {    // Already exists, do update
                // But only if value changed
                if ($currentResponses[$fieldName] != $response) {
                    $this->resultFetcher->updateTable($this->responseTable, $data, [
                        'gdr_id_token' => $tokenId,
                        'gdr_answer_id' => $fieldName,
                        'gdr_answer_row' => 1,
                    ]);
                }
            } else {
                // We add the inserts together in the next prepared statement to improve speed
                $inserts[$fieldName] = $data;
            }
        }

        if (count($inserts) > 0) {
            $platform = $this->resultFetcher->getPlatform();
            $fields = array_map([$platform, 'quoteIdentifier'], array_keys(reset($inserts)));
            $sql = 'INSERT INTO ' . $this->responseTable . ' (' . join(', ', $fields) . ') VALUES ';

            $values = [];
            $insertRows = [];
            foreach($inserts as $row) {
                $values[] = $row['gdr_response'];
                $row['gdr_response'] = '?';
                $insertRows[] = '(' . join(', ', array_map(function($value) use ($platform) {
                    if ($value !== '?') {
                        return $platform->quoteValue($value);
                    }
                    return $value;
                    }, $row)) . ')';
            }

            $sql .= join(', ', $insertRows);

            //$sql .= join(', ', array_fill(1, count($fields), '?')) . ')';

            $this->resultFetcher->query($sql, $values);
        }
    }

    protected function getCurrentResponses(string $tokenId): array
    {
        $select = $this->resultFetcher->getSelect($this->responseTable);
        $select->columns([
            'gdr_answer_id',
            'gdr_response',
        ])->where([
            'gdr_id_token' => $tokenId,
        ]);

        return $this->resultFetcher->fetchPairs($select);
    }

    protected function getDefaultRowData(string $tokenId, int $userId): array
    {
        return [
            'gdr_id_token'   => $tokenId,
            'gdr_changed_by' => $userId,
            'gdr_created_by' => $userId,
        ];
    }

    /**
     * Get a name for the view
     *
     * @param Survey $survey
     * @return string
     */
    protected function getViewName(Survey $survey): string
    {
        return 'T' . $survey->getSurveyId();
    }

    protected function removeMetaFields(string $tokenId, array $responses): array
    {
        $token = $this->tracker->getToken($tokenId);
        $source = $token->getSurvey()->getSource();
        if ($source instanceof SourceAbstract) {
            $metaFields = $source::$metaFields;
            foreach ($metaFields as $field) {
                if (array_key_exists($field, $responses)) {
                    unset($responses[$field]);
                }
            }
        }
        return $responses;
    }


    public function responseTableExists(): bool
    {
        $source = Factory::createSourceFromAdapter($this->responseDbAdapter);
        $tables = $source->getTableNames();

        return in_array($this->responseTable, $tables);
    }

    /**
     * @param Survey $survey
     * @param DataReaderInterface $answerModel
     * @return void
     */
    public function replaceCreateView(Survey $survey, DataReaderInterface $answerModel): void
    {
        $viewName = $this->getViewName($survey);

        $fieldSql   = '';

        $metaModel = $answerModel->getMetaModel();

        $platform = $this->responseDbAdapter->getPlatform();

        foreach ($metaModel->getItemsOrdered() as $name) {
            if (true === $metaModel->get($name, 'survey_question') && // It should be a question
                !in_array($name, ['submitdate', 'startdate', 'datestamp']) && // Leave out meta info
                !$metaModel->is($name, 'type', MetaModelInterface::TYPE_NOVALUE)) {         // Only real answers
                $fieldSql .= ',MAX(IF(gdr_answer_id = ' . $platform->quoteValue($name) . ', gdr_response, NULL)) AS ' . $platform->quoteIdentifier($name);
            }
        }

        if ($fieldSql > '') {
            $tokenTable = $platform->quoteIdentifier($this->gemsDbName . '.gems__tokens');
            $createViewSql = 'CREATE OR REPLACE VIEW ' . $platform->quoteIdentifier($viewName) . ' AS SELECT gdr_id_token';
            $createViewSql .= $fieldSql;
            $createViewSql .= "FROM gemsdata__responses join " . $tokenTable .
                " on (gto_id_token=gdr_id_token and gto_id_survey=" . $survey->getSurveyId() .
                ") GROUP BY gdr_id_token;";
                $this->resultFetcher->query($createViewSql);
        }
    }
}