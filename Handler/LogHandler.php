<?php

namespace Padam87\ErrorLogBundle\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Padam87\ErrorLogBundle\Entity\Emeddables\Exception;
use Padam87\ErrorLogBundle\Entity\Emeddables\Request;
use Padam87\ErrorLogBundle\Entity\Error;
use Padam87\ErrorLogBundle\Entity\Occurrence;
use Padam87\ErrorLogBundle\Entity\UserInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LogHandler extends AbstractProcessingHandler
{
    private Registry $registry;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;
    private string $rootDir;
    private array $config;

    private ?UserInterface $user = null;

    public function setDoctrine(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function setTokenStorage(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function setRequestStack(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    protected function write(array $record): void
    {
        try {
            $this->_write($record);
        } catch (\Exception $e) {
            // getting and Exception here would cause a inf. loop
        }
    }

    private function _write(array $record)
    {
        if ($record['level'] < Logger::ERROR) {
            return;
        }

        $context = $record['context'];
        $exception = array_key_exists('exception', $context) ? $context['exception'] : null;

        if ($exception === null) {
            return;
        }

        if ($exception instanceof HttpException && $exception->getStatusCode() < 500) {
            return;
        }

        if ($exception instanceof \Throwable && !$this->isIgnored($exception)) {
            $this->writeException($record, $exception);

            $this->registry->getManager()->flush();
        }
    }

    private function isIgnored(\Throwable $exception): bool
    {
        foreach ($this->config['ignored_exceptions'] as $trace) {
            $current = $exception;

            foreach ($trace as $class) {
                if ($current !== null && get_class($current) !== $class) {
                    break 2;
                }

                $current = $current->getPrevious();
            }

            return true;
        }

        return false;
    }

    private function writeException(array $record, \Throwable $exception): Error
    {
        $e = Exception::fromException($exception, $this->rootDir);

        if (\is_callable([$this->requestStack, 'getMainRequest'])) {
            $request = $this->requestStack->getMainRequest();   // symfony 5.3+
        } else {
            $request = $this->requestStack->getMasterRequest();
        }

        if (null !== $request) {
            $r = Request::fromRequest($request);
        } elseif (php_sapi_name() === 'cli') {
            $r = Request::fromInput(new ArgvInput());
        }

        $unique = $this->createUniqueHash($r, $e);

        $em = $this->registry->getManager();
        $user = $this->getUser();

        if (!$em->isOpen()) {
            $em = $this->registry->resetManager();

            if ($user) {
                $this->user = $user = $em->find(UserInterface::class, $user->getId());
            }
        }

        $repo = $em->getRepository(Error::class);

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

    public function createUniqueHash(Request $request, Exception $exception)
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
