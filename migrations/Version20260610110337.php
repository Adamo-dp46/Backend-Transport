<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610110337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gare ADD datecreation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE personnel ADD dateembauche DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE is_founder is_founder TINYINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gare DROP datecreation');
        $this->addSql('ALTER TABLE personnel DROP dateembauche');
        $this->addSql('ALTER TABLE `user` CHANGE is_founder is_founder TINYINT NOT NULL');
    }
}
