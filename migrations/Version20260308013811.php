<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308013811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bookshop_order_promotion (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, promotion_id INT NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_EBED1FA88D9F6D38 (order_id), INDEX IDX_EBED1FA8139DF194 (promotion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bookshop_order_promotion ADD CONSTRAINT FK_EBED1FA88D9F6D38 FOREIGN KEY (order_id) REFERENCES bookshop_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bookshop_order_promotion ADD CONSTRAINT FK_EBED1FA8139DF194 FOREIGN KEY (promotion_id) REFERENCES bookshop_promotion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookshop_order_promotion DROP FOREIGN KEY FK_EBED1FA88D9F6D38');
        $this->addSql('ALTER TABLE bookshop_order_promotion DROP FOREIGN KEY FK_EBED1FA8139DF194');
        $this->addSql('DROP TABLE bookshop_order_promotion');
    }
}
