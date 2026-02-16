<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;

class ListUsersForSubscriptionAction extends UserAction
{
    protected function action(): Response
    {
        $users = $this->userRepository->findAll();
        $items = array_map(static function ($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'rol' => $user->getRol(),
            ];
        }, $users);

        return $this->respondWithData($items);
    }
}
