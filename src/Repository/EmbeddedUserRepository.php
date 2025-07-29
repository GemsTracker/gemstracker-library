<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Db\ResultFetcher;
use Gems\Event\LayoutParamsEvent;
use Gems\Exception;
use Gems\Menu\Menu;
use Gems\Middleware\MenuMiddleware;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\Tracker;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\Embed\EmbedLoader;
use Gems\User\User;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Gems
 * @subpackage Repository
 * @since      Class available since version 1.0
 */
class EmbeddedUserRepository implements EventSubscriberInterface
{
    const SESSION_ID = 'embedded_user_data';

    protected array $data = [];

    protected SessionInterface $session;

    public function __construct(
        protected readonly EmbedLoader $embedLoader,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TranslatorInterface $translator,
    )
    { }

    public function applyToLayoutParamsEvent(LayoutParamsEvent $event): void
    {
        if (! $this->data) {
            return;
        }

        // dump(array_keys($event->getParams()), array_keys($this->data));

        $parameters = $event->getParams();
        $parameters['idle_check'] = false;

        switch ($this->data['gsus_hide_breadcrumbs'] ?? '') {
            case 'no_display':
                unset($parameters['breadcrumbs'], $parameters['title_breadcrumbs']);
                break;

            case 'no_top':
                array_shift($parameters['breadcrumbs']);
                break;
        }

        // dump(get_class($redirect));
        // gsus_redirect

        switch ($this->data['gsus_deferred_menu_layout'] ?? '') {
            case 'none':
                unset($parameters[MenuMiddleware::MENU_ATTRIBUTE]);
                break;

            case 'current':
                $redirect = $this->embedLoader->loadRedirect($this->data['gsus_redirect'] ?? 'Gems\\User\\Embed\\Redirect\\RespondentShowPage');
                /**
                 * @var Menu $menu
                 */
                $menu = $parameters['mainMenu'];
                $menu->setMenuRoot($redirect->getBaseMenuRouteName());
                break;
        }


        $event->setParams($parameters);
    }

    public function checkRequest(ServerRequestInterface $request): void
    {
        $this->setRequest($request);

        if (isset($this->data['patientNr'], $this->data['organizationId'], $this->data['respondentId'])) {
            if (! $this->data['respondentId']) {
                // This can happen when a new patient is created
                $this->updateRespondentId($this->data['patientNr'], $this->data['organizationId']);
            }

            $params = $request->getQueryParams();
            $routeResult = $request->getAttribute(RouteResult::class);

            if ($routeResult instanceof RouteResult) {
                $params = $routeResult->getMatchedParams() + $params;
            }

            if (isset($params[MetaModelInterface::REQUEST_ID1], $params[MetaModelInterface::REQUEST_ID2])) {
                if ($this->data['patientNr'] == $params[MetaModelInterface::REQUEST_ID1] && $this->data['organizationId'] == $params[MetaModelInterface::REQUEST_ID2]) {
                    return;
                }
                $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
                if ($user instanceof User) {
                    $user->assertAccessToOrganizationId((int) $params[MetaModelInterface::REQUEST_ID2], $this->data['respondentId']);
                    return;
                }

                throw new Exception(sprintf($this->translator->_('Access allowed only for patient nr %d, not for %s.'), $this->data['patientNr'], $params[MetaModelInterface::REQUEST_ID1]));
            }
        }
    }

    public function getEmbeddedData(): array
    {
        return $this->data;
    }

    public static function getSubscribedEvents()
    {
        return [
            LayoutParamsEvent::class => ['applyToLayoutParamsEvent'],
        ];
    }
    public function getTemplate(): string
    {
        return $this->data['gsus_deferred_mvc_layout'] ?? GemsSnippetResponder::DEFAULT_TEMPLATE;
    }

    public function hasEmbeddedData(): bool
    {
        return (bool) $this->session->get(self::SESSION_ID);
    }

    public function setEmbeddedData(EmbeddedUserData $embeddedUserData): void
    {
        $this->data = $embeddedUserData->getArrayCopy() + $this->data;

        $this->updateSession();
    }

    public function setPatientNr(string $patientNr, int $organizationId): void
    {
        $this->data['patientNr']      = $patientNr;
        $this->data['organizationId'] = $organizationId;

        $this->updateRespondentId($patientNr, $organizationId);
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->setSession($request->getAttribute(SessionInterface::class));
    }

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
        if ($this->session->has(self::SESSION_ID)) {
            $this->data = $this->session->get(self::SESSION_ID);
        }
    }

    protected function updateRespondentId($patientNr, int $organizationId): void
    {
        $select = $this->resultFetcher->getSelect('gems__respondent2org');
        $select->columns(['gr2o_id_user'])
            ->where([
                'gr2o_patient_nr' => $patientNr,
                'gr2o_id_organization' => $organizationId,
            ]);

        $this->data['respondentId'] = $this->resultFetcher->fetchOne($select);

        $this->updateSession();
    }


    protected function updateSession(): void
    {
        $this->session->set(self::SESSION_ID, $this->data);
    }
}