<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Infrastructure\Security\JwtServiceInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;

class VerifyLoginCodeAction extends Action
{
    private UserRepository $userRepository;
    private JwtServiceInterface $jwtService;

    public function __construct(LoggerInterface $logger, UserRepository $userRepository, JwtServiceInterface $jwtService)
    {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->jwtService = $jwtService;
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $data = $this->getFormData();
        $email = $data['email'] ?? null;
        $code = $data['code'] ?? null;
        if (!$email || !$code) {
            throw new HttpBadRequestException($this->request, 'Email and code required');
        }

        // fetch code
        $codeHash = $this->userRepository->getCodeByEmail($email);
        if ($codeHash === null) {
            throw new HttpBadRequestException($this->request, 'No pending code for this email');
        }

        if (!password_verify((string)$code, $codeHash)) {
            return $this->respondWithData(['ok' => false, 'message' => 'Invalid code'], 400);
        }

        // find user to get id and info
        try {
            $user = $this->userRepository->findByEmail($email);
        } catch (\Exception $e) {
            throw new HttpBadRequestException($this->request, 'User not found');
        }

        // clear password
        $this->userRepository->updatePassword((int)$user->getId(), null);

        // generate JWT with minimal user info
        $payload = [
            'sub' => (int)$user->getId(),
            'email' => $email,
            'rol' => method_exists($user, 'getRol') ? $user->getRol() : 'user',
        ];

        $token = $this->jwtService->generate($payload);

        return $this->respondWithData(['ok' => true, 'token' => $token]);
    }
}
