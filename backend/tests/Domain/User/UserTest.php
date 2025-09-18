<?php

declare(strict_types=1);

namespace Tests\Domain\User;

use App\Domain\User\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function userProvider(): array
    {
        return [
            [1, 'Bill Gates', 'bill.gates@example.com', 'user'],
            [2, 'Steve Jobs', 'steve.jobs@example.com', 'user'],
            [3, 'Mark Zuckerberg', 'mark.zuckerberg@example.com', 'user'],
            [4, 'Evan Spiegel', 'evan.spiegel@example.com', 'user'],
            [5, 'Jack Dorsey', 'jack.dorsey@example.com', 'user'],
        ];
    }

    /**
     * @dataProvider userProvider
     * @param int    $id
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     */
    public function testGetters(int $id, string $name, string $email, string $rol)
    {
        $user = new User($id, $name, $email, $rol);

        $this->assertEquals($id, $user->getId());
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($rol, $user->getRol());
    }

    /**
     * @dataProvider userProvider
     * @param int    $id
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     */
    public function testJsonSerialize(int $id, string $name, string $email, string $rol)
    {
        $user = new User($id, $name, $email, $rol);

        $expectedPayload = json_encode([
            'id' => $id,
            'name' => $name,
            'email' => strtolower($email),
            'rol' => $rol,
            'codigo' => null,
            'fecha_expedicion' => null,
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);

        $this->assertEquals($expectedPayload, json_encode($user));
    }
}
