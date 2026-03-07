<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\User\Entity\User;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;

final class UserContext implements Context
{
    /** @var array<string, User> */
    protected array $users = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getUser(string $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    /**
     * @Given /^there exist following users:$/
     */
    public function thereExistFollowingUsers(TableNode $table): void
    {
        foreach ($table as $row) {
            $user = $this->getUser($row['id']);
            if (!$user) {
                $user = new User();
                $user->setName($row['name']);
                $this->em->persist($user);

                $this->users[$row['id']] = $user;
            }
        }

        $this->em->flush();
    }
}
