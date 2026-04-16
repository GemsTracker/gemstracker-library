<?php

namespace Gems\Communication\Unsubscribe\Messenger\Handler;


use Gems\Communication\Unsubscribe\Messenger\Message\SubscriptionInfo;
use Gems\Db\ResultFetcher;
use Gems\Model\Respondent\RespondentMailstatusLogModel;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ChangeSubscriptionValueHandler
{
    public function __construct(
        private readonly ResultFetcher $resultFetcher,
        private readonly RespondentMailstatusLogModel $respondentMailstatusLogModel,
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
                $patientInfo['gr2o_mailable'],
                $unsubscribeInfo->subscriptionValue,
                $unsubscribeInfo->comment
            );
        }
    }

    private function updateRespondentMailable(string $email, int $organizationId, int $subscriptionValue): void
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
        $values['glrm_id_user'] = $respondentId;
        $values['glrm_id_organization'] = $organizationId;
        $values['glrm_mailable_field'] = 'gr2o_mailable';
        $values['glrm_old_mailable'] = $oldCode;
        $values['glrm_new_mailable'] = $newCode;
        $values['glrm_comment'] = $comment;
        $values['glrm_created'] = "CURRENT_TIMESTAMP";
        $values['glrm_created_by'] = $respondentId;

        $this->respondentMailstatusLogModel->save($values);
    }
}
