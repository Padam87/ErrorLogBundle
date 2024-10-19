<?php

namespace Padam87\ErrorLogBundle\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Padam87\ErrorLogBundle\Entity\Emeddables\Exception;
use Padam87\ErrorLogBundle\Entity\Emeddables\Request;
use Padam87\ErrorLogBundle\Entity\Error;
use Padam87\ErrorLogBundle\Entity\Occurrence;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LogHandler extends AbstractProcessingHandler
{
    private Registry $registry;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private string $rootDir;
    private array $config;

    private ?UserInterface $user = null;

    public function setDoctrine(Registry $registry): void
    {
        $this->registry = $registry;
    }

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    public function setRootDir(string $rootDir): void
    {
        $this->rootDir = $rootDir;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    protected function write(array|LogRecord $record): void
    {
        try {
            $this->_write($record);
        } catch (\Exception $e) {
            // getting and Exception here would cause a inf. loop
        }
    }

    private function _write(array|LogRecord $record): void
    {
        if ($record['level'] < Logger::ERROR) {
            return;
        }

        $context = $record['context'];
        $exception = $context['exception'] ?? null;

        if ($exception === null) {
            return;
        }

        if ($exception instanceof HttpException && $exception->getStatusCode() < 500) {
            return;
        }

        if ($exception instanceof \Throwable && !$this->isIgnored($exception)) {
            $this->writeException($record, $exception);

            $this->registry->getManager($this->config['entity_manager_name'])->flush();
        }
    }

    private function isIgnored(\Throwable $exception): bool
    {
        foreach ($this->config['ignored_exceptions'] as $trace) {
            $current = $exception;

            foreach ($trace as $class) {
                if ($current !== null && $current::class !== $class) {
                    break 2;
                }

                $current = $current->getPrevious();
            }

            return true;
        }

        return false;
    }

    private function writeException(array|LogRecord $record, \Throwable $exception): Error
    {
        $e = Exception::fromException($exception, $this->rootDir);
        $request = $this->requestStack->getMainRequest();

        if (null !== $request) {
            $r = Request::fromRequest($request);
        } elseif (php_sapi_name() === 'cli') {
            $r = Request::fromInput(new ArgvInput());
        }

        $unique = $this->createUniqueHash($r, $e);

        $em = $this->registry->getManager($this->config['entity_manager_name']);

        if (!$em->isOpen()) {
            $em = $this->registry->resetManager($this->config['entity_manager_name']);
        }

        $repo = $em->getRepository(Error::class);

        $user = $this->getUser();

        if (null === $error = $repo->findOneBy(['uniqueHash' => $unique])) {
            $error = new Error();
            $error
                ->setUniqueHash($unique)
                ->setLevel($record['level_name'])
                ->setMethod($r->getMethod())
                ->setRoute($r->getAttributes()['_route'])
                ->setException($e)
            ;

            $em->persist($error);
        }

        $occurrence = new Occurrence();
        $occurrence
            ->setError($error)
            ->setRequest($r)
            ->setException($e)
            ->setLoggedAt($record['datetime'])
            ->setUser($user)
        ;

        $em->persist($occurrence);

        $error->addOccurrence($occurrence);

        if ($exception->getPrevious() && $exception->getPrevious() instanceof \Throwable) {
            $previous = $this->writeException($record, $exception->getPrevious());

            $error->setPrevious($previous);
        }

        return $error;
    }

    public function createUniqueHash(Request $request, Exception $exception): string
    {
        $string = implode(',', [
            $request->getMethod(),
            $request->getAttributes()['_route'],
            $exception->getClass(),
            $exception->getFile(),
            $exception->getLine(),
        ]);

        return sha1($string);
    }

    private function getUser(): ?UserInterface
    {
        if ($this->user === null) {
            if (null === $token = $this->tokenStorage->getToken()) {
                return null;
            }

            $user = $token->getUser();

            if (!$user instanceof UserInterface) {
                return null;
            }

            $this->user = $user;
        }

        return $this->user;
    }
}
