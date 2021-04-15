<?php

/*
 * This file is part of the mesolaries/SmartApiBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Emil Manafov <mnf.emil@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Mesolaries\SmartApiBundle\EventListener;


use Mesolaries\SmartApiBundle\Exception\SmartProblemException;
use Mesolaries\SmartApiBundle\Problem\SmartProblem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Emil Manafov <mnf.emil@gmail.com>
 */
class SmartProblemExceptionListener implements EventSubscriberInterface
{
    private $debug;

    private $pattern;

    public function __construct($debug, $pattern)
    {
        $this->debug   = $debug;
        $this->pattern = $pattern;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (null === $this->pattern || !preg_match('{' . $this->pattern . '}', rawurldecode($request->getPathInfo()))) {
            if (false === mb_strpos((string)$request->getPreferredFormat(), 'json') &&
                false === mb_strpos((string)$request->getContentType(), 'json')) {
                return;
            }
        }

        $e = $event->getThrowable();

        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        if ($this->debug && $statusCode >= 500) {
            return;
        }

        if ($e instanceof SmartProblemException) {
            $smartProblem = $e->getSmartProblem();
        } else {
            $smartProblem = new SmartProblem($statusCode);

            /*
             * If it's an HttpException message (e.g. for 404, 403),
             * we'll say as a rule that the exception message is safe
             * for the client. Otherwise, it could be some sensitive
             * low-level exception, which should *not* be exposed
             */
            if ($e instanceof HttpExceptionInterface) {
                $smartProblem->addExtraData('detail', $e->getMessage());
            }
        }

        $response = new JsonResponse($smartProblem->normalize(), $smartProblem->getStatusCode());
        $response->headers->set('Content-Type', 'application/problem+json');

        foreach ($e->getHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        $event->setResponse($response);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}