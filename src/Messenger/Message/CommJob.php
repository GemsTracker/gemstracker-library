<?php

namespace Gems\Messenger\Message;

use Gems\Exception;

class CommJob
{
    public function __construct(
        private readonly array $jobData,
        private readonly bool $preview = false,
        private readonly bool $force = false,
    )
    {
        if (! isset($this->jobData['gcj_id_job'])) {
            throw new Exception("Missing value for job data field 'gcj_id_job'.");
        }
        foreach(['gcj_id_communication_messenger', 'gcj_id_order', 'gct_id_template'] as $field) {
            if (! isset($this->jobData[$field])) {
                $id = $this->getId();
                throw new Exception("Missing value for job id $id for field '$field'.");
            }
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->jobData['gcj_id_job'];
    }

    /**
     * @return int
     */
    public function getMessengerId(): int
    {
        return (int) $this->jobData['gcj_id_communication_messenger'];
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return (int) $this->jobData['gcj_id_order'];
    }

    /**
     * @return int
     */
    public function getTemplateId(): int
    {
        return (int) $this->jobData['gct_id_template'];
    }

    public function isForced(): bool
    {
        return $this->force;
    }

    public function isPreview() : bool
    {
        return $this->preview;
    }
}