<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240301131428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE receipt DROP CONSTRAINT fk_5399b6453c450273');
        $this->addSql('DROP INDEX idx_5399b6453c450273');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN start_application TO start_apply_at');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN end_application TO end_apply_at');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN contract_id_id TO contract_id');
        $this->addSql('ALTER TABLE receipt ADD CONSTRAINT FK_5399B6452576E0FD FOREIGN KEY (contract_id) REFERENCES contract (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5399B6452576E0FD ON receipt (contract_id)');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT fk_723705d19bdc2a67');
        $this->addSql('DROP INDEX idx_723705d19bdc2a67');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN receipt_id_id TO receipt_id');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN payment_date TO paid_at');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN failure_date TO failed_at');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D12B5CA896 FOREIGN KEY (receipt_id) REFERENCES receipt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_723705D12B5CA896 ON transaction (receipt_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D12B5CA896');
        $this->addSql('DROP INDEX IDX_723705D12B5CA896');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN receipt_id TO receipt_id_id');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN paid_at TO payment_date');
        $this->addSql('ALTER TABLE transaction RENAME COLUMN failed_at TO failure_date');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT fk_723705d19bdc2a67 FOREIGN KEY (receipt_id_id) REFERENCES receipt (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_723705d19bdc2a67 ON transaction (receipt_id_id)');
        $this->addSql('ALTER TABLE receipt DROP CONSTRAINT FK_5399B6452576E0FD');
        $this->addSql('DROP INDEX IDX_5399B6452576E0FD');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN start_apply_at TO start_application');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN end_apply_at TO end_application');
        $this->addSql('ALTER TABLE receipt RENAME COLUMN contract_id TO contract_id_id');
        $this->addSql('ALTER TABLE receipt ADD CONSTRAINT fk_5399b6453c450273 FOREIGN KEY (contract_id_id) REFERENCES contract (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5399b6453c450273 ON receipt (contract_id_id)');
    }
}
