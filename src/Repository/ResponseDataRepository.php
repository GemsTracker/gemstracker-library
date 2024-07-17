<?php

namespace Gems\Repository;

use Gems\Db\ResponseDbAdapter;
use Gems\Db\ResultFetcher;
use Gems\Tracker;
use Gems\Tracker\Source\SourceAbstract;
use Laminas\Db\Metadata\Source\Factory;

class ResponseDataRepository
{
    protected ResultFetcher $resultFetcher;

    protected string $responseTable = 'gemsdata__responses';

    public function __construct(
        protected readonly ResponseDbAdapter $responseDbAdapter,
        protected readonly Tracker $tracker,
    )
    {
        $this->resultFetcher = new ResultFetcher($this->responseDbAdapter);
    }

    public function addResponses(string $tokenId, array $responses, int $userId)
    {
        $defaultData = $this->getDefaultRowData($tokenId, $userId);
        //$responses = $this->removeMetaFields($tokenId, $responses);

        $currentResponses = $this->getCurrentResponses($tokenId);
        $inserts = [];

        foreach($responses as $fieldName => $response) {
            $data = $defaultData;
            $data['gdr_answer_id'] = $fieldName;
            if (is_array($response)) {
                $response = join('|', $response);
            }
            $data['gdr_response'] = $response;
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
    
}