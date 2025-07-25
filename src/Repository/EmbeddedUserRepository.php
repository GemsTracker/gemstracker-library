<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Repository
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Repository;

use Gems\Event\LayoutParamsEvent;
use Gems\Menu\Menu;
use Gems\Middleware\MenuMiddleware;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\Embed\EmbedLoader;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        $this->session->set(self::SESSION_ID, $embeddedUserData->getArrayCopy());
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
}