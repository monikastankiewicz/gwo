<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Promotion\Entity\Promotion;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;

class PromotionContext implements Context
{
    /** @var array<string, Promotion> */
    protected array $promotions = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @Given /^there exist following promotions:$/
     */
    public function thereExistFollowingPromotions(TableNode $table): void
    {
        foreach ($table as $row) {
            $promotion = new Promotion();
            $promotion->setType($this->resolvePromotionType($row['type']));
            $promotion->setPercentageDiscount((int)$row['percentageDiscount']);

            if (!empty($row['productTypesFilter'])) {
                $promotion->setProductTypesFilter([$row['productTypesFilter']]);
            }

            $this->promotions[$row['id']] = $promotion;

            $this->em->persist($promotion);
        }

        $this->em->flush();
    }

    private function resolvePromotionType(string $type): int
    {
        return match ($type) {
            'item' => Promotion::TYPE_ITEM,
            'order' => Promotion::TYPE_ORDER,
            default => throw new \RuntimeException(sprintf('Unknown promotion type "%s".', $type)),
        };
    }

    public function getPromotion(string $id): ?Promotion
    {
        return $this->promotions[$id] ?? null;
    }
}
