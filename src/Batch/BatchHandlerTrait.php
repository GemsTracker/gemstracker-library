<?php


namespace Gems\Batch;


trait BatchHandlerTrait
{
    /**
     * @var \MUtil\Batch\BatchAbstract
     */
    protected $batch;

    /**
     * @param $message string batch message
     * @return bool
     */
    protected function addBatchMessage($message)
    {
        if ($this->batch instanceof \MUtil\Batch\BatchAbstract) {
            $this->batch->addMessage($message);
            return true;
        }
        return false;
    }

    /**
     * @param $counterName
     * @param int $add
     * @return bool
     */
    protected function addBatchCount($counterName, $add = 1)
    {
        if ($this->batch instanceof \MUtil\Batch\BatchAbstract) {
            $this->batch->addToCounter($counterName, $add);
            return true;
        }
        return false;
    }

    /**
     * Set the batch
     *
     * @param \MUtil\Batch\BatchAbstract $batch
     */
    public function setBatch(\MUtil\Batch\BatchAbstract $batch)
    {
        $this->batch = $batch;
    }
}
