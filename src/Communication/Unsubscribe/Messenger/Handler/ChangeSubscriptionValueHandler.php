<?php

namespace Gems\Communication\Unsubscribe\Messenger\Handler;


use Gems\Communication\Unsubscribe\Messenger\Message\SubscriptionInfo;
use Gems\Db\ResultFetcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ChangeSubscriptionValueHandler
{
    /**
     * @var int The value to assign while unsubscribing
     */
    protected int $unsubscribedValue = 0;

    public function __construct(
        private readonly ResultFetcher $resultFetcher,
    )
    {
    }

    public function __invoke(SubscriptionInfo $unsubscribeInfo): void
    {
        $respondentList = $this->getRespondentInfo($unsubscribeInfo->email, $unsubscribeInfo->organizationId);

        if (!count($respondentList)) {
            return;
        }

        $this->updateRespondentMailable(
            $unsubscribeInfo->email,
            $unsubscribeInfo->organizationId,
            $unsubscribeInfo->subscriptionValue
        );

        foreach($respondentList as $patientInfo) {
            if ($patientInfo['gr2o_mailable'] === $unsubscribeInfo->subscriptionValue) {
                continue;
            }

            $this->logMailableChange(
                $patientInfo['gr2o_id_user'],
                $unsubscribeInfo->organizationId,
                $this->unsubscribedValue,
                $patientInfo['gr2o_mailable'],
                $unsubscribeInfo->comment
            );
        }
    }

    private function updateRespondentMailable(string $email, string $organizationId, int $subscriptionValue): void
    {
        $sql = 'UPDATE gems__respondent2org
            SET gr2o_mailable = :mailable, gr2o_changed_by = gr2o_id_user
            WHERE gr2o_email = :email 
              AND gr2o_id_organization = :organizationId
              AND gr2o_mailable != 0
              AND gr2o_mailable != :mailable';

        $this->resultFetcher->query($sql, [
            'mailable' => $subscriptionValue,
            'email' => $email,
            'organizationId' => $organizationId,
        ]);
    }

    private function getRespondentInfo(string $email, int $organizationId): array
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2org')
            ->columns([
                'gr2o_patient_nr',
                'gr2o_id_organization',
                'gr2o_id_user',
                'gr2o_mailable',
            ])
            ->where([
                'gr2o_email' => $email,
                'gr2o_id_organization' => $organizationId,
            ]);

        return $this->resultFetcher->fetchAll($select);
    }

    private function logMailableChange(
        int $respondentId,
        int $organizationId,
        int $oldCode,
        int $newCode,
        string|null $comment,
    ): void
    {
        // TODO: Log unsubscribe
    }
}