<?php

declare(strict_types=1);

namespace Bigoen\MercureTwig\Fragment;

use Bigoen\MercureTwig\Event\MercureTwigFragment;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\HttpKernel\Fragment\RoutableFragmentRenderer;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * @author Şafak Saylam <safak@bigoen.com>
 */
class MercureTwigFragmentRenderer extends RoutableFragmentRenderer
{
    private FragmentRendererInterface $inlineRenderer;
    private UriSigner $signer;
    private EventDispatcherInterface $eventDispatcher;
    private string $hubUrl;

    public function __construct(
        FragmentRendererInterface $inlineRenderer,
        UriSigner $signer,
        EventDispatcherInterface $eventDispatcher,
        string $hubUrl
    ) {
        $this->inlineRenderer = $inlineRenderer;
        $this->signer = $signer;
        $this->eventDispatcher = $eventDispatcher;
        $this->hubUrl = $hubUrl;
    }

    public function render($uri, Request $request, array $options = []): Response
    {
        if (!$uri instanceof ControllerReference) {
            throw new InvalidArgumentException('Twig renderer can only be used with a controller reference.');
        }

        if (!isset($options['topics']) || !is_array($options['topics'])) {
            throw new InvalidArgumentException('The `topics` option must be set and must be an array of strings.');
        }

        $fragmentUri = $this->generateSignedFragmentUri($uri, $request);
        $fragmentId = sha1($fragmentUri);

        $response = $this->inlineRenderer->render($uri, $request, $options);

        // Let the subscribers know about the fragment behind rendered
        $this->eventDispatcher->dispatch(new MercureTwigFragment(
            $fragmentId,
            $fragmentUri,
            $options['topics']
        ));

        $topics = implode(',', $options['topics']);
        $isAdd = $options['isAdd'] ?? 0;
        $class = $options['class'] ?? '';
        $content = "
                  <bigoen-mercure-twig 
                      id='{$fragmentId}' 
                      url='{$fragmentUri}' 
                      topics='{$topics}' 
                      hub='{$this->hubUrl}'
                      is_add='{$isAdd}'
                      class='{$class}'
                  >
                  {$response->getContent()}
                  </bigoen-merucre-twig>";

        $response->setContent($content);

        return $response;
    }

    private function generateSignedFragmentUri($uri, Request $request): string
    {
        // we need to sign the absolute URI, but want to return the path only.
        $fragmentUri = $this->signer->sign($this->generateFragmentUri($uri, $request, true));

        return substr($fragmentUri, strlen($request->getSchemeAndHttpHost()));
    }

    public function getName(): string
    {
        return 'bigoen_mercure_twig';
    }
}
