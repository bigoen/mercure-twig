<?php

declare(strict_types=1);

namespace Bigoen\MercureTwig\EventSubscriber;

use Bigoen\MercureTwig\Event\MercureTwigFragment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @author Şafak Saylam <safak@bigoen.com>
 */
class MercureTwigJavaScriptSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private bool $hasSubscriptions = false;
    private string $subscriberJs;

    public function __construct(Environment $twig, string $subscriberJs)
    {
        $this->twig = $twig;
        $this->subscriberJs = $subscriberJs;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -128],
            MercureTwigFragment::class => 'onFragmentRenderer',
        ];
    }

    public function onFragmentRenderer(): void
    {
        $this->hasSubscriptions = true;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest() || !$this->hasSubscriptions) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false === $pos) {
            return;
        }

        $toolbar = "\n".str_replace("\n", '', $this->twig->render($this->subscriberJs))."\n";

        $content = substr($content, 0, $pos).$toolbar.substr($content, $pos);
        $response->setContent($content);
    }
}
