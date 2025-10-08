<?php

namespace GemsTest\Tracker;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Log\Loggers;
use Gems\Project\ProjectSettings;
use Gems\Repository\ConsentRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\ReceptionCodeRepository;
use Gems\Repository\RespondentRepository;
use Gems\Repository\TokenRepository;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;

class TokenTest extends TestCase
{
    private function getToken(array $tokenData, array $dependencies = []): Token
    {
        $maskRepository = $this->createMock(MaskRepository::class);
        $maskRepository->method('applyMaskToRow')->willReturnArgument(0);

        return new Token(
            $tokenData,
            $dependencies[ResultFetcher::class] ?? $this->createStub(ResultFetcher::class),
            $dependencies[MaskRepository::class] ?? $maskRepository,
            $dependencies[Tracker::class] ?? $this->createStub(Tracker::class),
            $dependencies[ProjectSettings::class] ?? $this->createStub(ProjectSettings::class),
            $dependencies[ConsentRepository::class] ?? $this->createStub(ConsentRepository::class),
            $dependencies[OrganizationRepository::class] ?? $this->createStub(OrganizationRepository::class),
            $dependencies[ReceptionCodeRepository::class] ?? $this->createStub(ReceptionCodeRepository::class),
            $dependencies[RespondentRepository::class] ?? $this->createStub(RespondentRepository::class),
            $dependencies[ProjectOverloader::class] ?? $this->createStub(ProjectOverloader::class),
            $dependencies[Translated::class] ?? $this->createStub(Translated::class),
            $dependencies[Locale::class] ?? $this->createStub(Locale::class),
            $dependencies[TokenRepository::class] ?? $this->createStub(TokenRepository::class),
            $dependencies[EventDispatcherInterface::class] ?? $this->createStub(EventDispatcherInterface::class),
            $dependencies[TranslatorInterface::class] ?? $this->createStub(TranslatorInterface::class),
           $dependencies[MessageBusInterface::class] ?? $this->createStub(MessageBusInterface::class),
            $dependencies[Loggers::class] ?? $this->createStub(Loggers::class),
            $dependencies[CurrentUserRepository::class] ?? $this->createStub(CurrentUserRepository::class),
        );
    }

    public function testSetResult(): void
    {
        $survey = $this->createMock(Tracker\Survey::class);
        $survey->method('getResultField')->willReturn('my_result');

        $metaModel = $this->createMock(MetaModelInterface::class);
        $metaModel->method('get')->with('gto_result', 'maxlength')->willReturn('255');
        $model = $this->createMock(Tracker\Model\TokenModel::class);
        $model->method('getMetaModel')->willReturn($metaModel);

        $trackEngine = $this->createMock(Tracker\Engine\TrackEngineInterface::class);
        $trackEngine->method('getTokenModel')->willReturn($model);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getSurvey')->with(10)->willReturn($survey);
        $tracker->method('getTrackEngine')->willReturn($trackEngine);
        $tracker->method('filterChangesOnly')->willReturnArgument(1);

        $resultFetcher = $this->createMock(ResultFetcher::class);

        $usedValues = [];

        $resultFetcher->method('updateTable')->willReturnCallBack(function($tableName, $values, $where) use (&$usedValues) {
            $usedValues = $values;
            return 1;
        });

        $token = $this->getToken([
            'gto_id_token' => 'abcd-1234',
            'gto_id_survey' => 10,
            'gto_id_track' => 100,
        ], [
            Tracker::class => $tracker,
            ResultFetcher::class => $resultFetcher,
        ]);

        $token->setResult([
            'my_result' => '1234',
        ], 1);

        $this->assertEquals('1234', $usedValues['gto_result']);


        $token->setResult([
            'my_result' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
        ], 1);
        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor ',
            $usedValues['gto_result']
        );


        $token->setResult([
            'my_result' => 'Lörém ïpsum dôlor sit àmet, cönsectetur âdïpiscing élït. Véstäbülum çursus ést àliquam, ëu frïngïlla nïsl. Crëpë pâté naïef façade résumé coöperatie. Üt énim àd mïnïm véniam, qüïs nostrud éxercïtation ullamco laboris nïsi üt àliqüip éx éa commodo consequat. Dëjà vü în réprehenderit în völüptate velit ësse çillum dôlore.',
        ], 1);
        $this->assertEquals('Lörém ïpsum dôlor sit àmet, cönsectetur âdïpiscing élït. Véstäbülum çursus ést àliquam, ëu frïngïlla nïsl. Crëpë pâté naïef façade résumé coöperatie. Üt énim àd mïnïm véniam, qüïs nostrud éxercïtation ullamco labori',
            $usedValues['gto_result']
        );
    }
}