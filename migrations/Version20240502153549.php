<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240502153549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste_four ADD email VARCHAR(255) NOT NULL, ADD phone_number VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE poste_one ADD email VARCHAR(255) NOT NULL, ADD phone_number VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE poste_three ADD email VARCHAR(255) NOT NULL, ADD phone_number VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE poste_two ADD email VARCHAR(255) NOT NULL, ADD phone_number VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE poste_two DROP email, DROP phone_number');
        $this->addSql('ALTER TABLE poste_three DROP email, DROP phone_number');
        $this->addSql('ALTER TABLE poste_one DROP email, DROP phone_number');
        $this->addSql('ALTER TABLE poste_four DROP email, DROP phone_number');
    }
}
