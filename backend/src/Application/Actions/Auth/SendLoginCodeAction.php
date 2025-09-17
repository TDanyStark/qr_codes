<?php

declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use Psr\Log\LoggerInterface;
use App\Infrastructure\Mailer\MailerInterface;
use App\Infrastructure\Mailer\MailException;
use Slim\Exception\HttpBadRequestException;

class SendLoginCodeAction extends Action
{
  private UserRepository $userRepository;
  private MailerInterface $mailer;

  public function __construct(LoggerInterface $logger, UserRepository $userRepository, MailerInterface $mailer)
  {
    parent::__construct($logger);
    $this->userRepository = $userRepository;
    $this->mailer = $mailer;
  }

  protected function action(): \Psr\Http\Message\ResponseInterface
  {
    $data = $this->getFormData();
    $email = $data['email'] ?? null;
    if (!$email) {
      throw new HttpBadRequestException($this->request, 'Email required');
    }

    // verify user exists
    try {
      $user = $this->userRepository->findByEmail($email);
    } catch (\Exception $e) {
      throw new HttpBadRequestException($this->request, 'Email not found');
    }

    // generate code
    $code = strval(random_int(100000, 999999));
    $hash = password_hash($code, PASSWORD_DEFAULT);

    // store code as password hash
    $this->userRepository->updatePassword((int)$user->getId(), $hash);

    $this->logger->info("Generated login code for {$email}");

    // send email with code (HTML body with paragraphs)
    $subject = "Your login code {$code}";
    $body = "<p>Tu código de acceso es: <strong>{$code}</strong></p>" .
      "<p>Si no lo solicitaste, ignora este mensaje.</p>";
    try {
      $this->mailer->send($email, $subject, $body);
    } catch (MailException $e) {
      // Log and continue - do not reveal mail internal errors to client
      $this->logger->error('Failed to send login code email', ['email' => $email, 'error' => $e->getMessage()]);
    }

    return $this->respondWithData(['message' => 'Code generated and sent (dev: check logs)']);
  }
}
