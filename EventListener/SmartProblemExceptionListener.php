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

    public function __construct($debug)
    {
        $this->debug = $debug;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $request = $event->getRequest();

        if (false === mb_strpos($request->getRequestFormat(), 'json') &&
            false === mb_strpos((string)$request->getContentType(), 'json')) {
            return;
        }

        $e = $event->getThrowable();

        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        if ($statusCode == 500 && $this->debug) {
            return;
        }

        if ($e instanceof SmartProblemException) {
            $smartProblem = $e->getSmartProblem();
        } else {
            $smartProblem = new SmartProblem($statusCode);
        }

        if ($e instanceof HttpExceptionInterface && !($e instanceof SmartProblemException)) {
            $smartProblem->addExtraData('detail', $e->getMessage());
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
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}