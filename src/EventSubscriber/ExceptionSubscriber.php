<?php

namespace App\EventSubscriber;

use App\Exception\TokenRevokedException;
use App\Exception\UserNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{

    /**
     * Handle the exception
     * @param ExceptionEvent $event The event
     * @return void The response
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException) {
            $data = [
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage()
            ];

        }

        else {
            $data = [
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $exception->getMessage()
            ];

        }

        $event->setResponse(new JsonResponse($data));
    }

    /**
     * Handle the response (for the token revoked exception and the user not found exception)
     * @param ResponseEvent $event The event
     * @return void The response
     */
    public function onKernelRequest(ResponseEvent $event): void
    {

        if ($event->getResponse()->getStatusCode() === Response::HTTP_UNAUTHORIZED) {

            if (str_contains($event->getResponse()->getContent(), "The token has been revoked.")) {
                $event->setResponse(new JsonResponse([
                    'status' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'The token has been revoked.'
                ], Response::HTTP_UNAUTHORIZED));
            }

            elseif (str_contains($event->getResponse()->getContent(), "User not found.")) {
                $event->setResponse(new JsonResponse([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'User not found.'
                ], Response::HTTP_UNAUTHORIZED));
            }

            else {
                $event->setResponse(new JsonResponse([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $event->getResponse()->getContent()
                ], Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        }
    }

    /**
     * Get the subscribed events
     * @return array The subscribed events
     */
    public static function getSubscribedEvents(): array
    {

        return [
            KernelEvents::EXCEPTION => ['onKernelException'],
            KernelEvents::RESPONSE => ['onKernelRequest']
        ];
    }
}
