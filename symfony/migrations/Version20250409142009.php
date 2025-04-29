<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409142009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author ADD first_name VARCHAR(50) NOT NULL, ADD last_name VARCHAR(50) NOT NULL, ADD biography LONGTEXT DEFAULT NULL, ADD birth_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD available_copies INT NOT NULL, ADD author_id INT NOT NULL, ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A33112469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_CBE5A331F675F31B ON book (author_id)');
        $this->addSql('CREATE INDEX IDX_CBE5A33112469DE2 ON book (category_id)');
        $this->addSql('ALTER TABLE category ADD name VARCHAR(50) NOT NULL, ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE loan ADD loan_date DATE NOT NULL, ADD due_date DATE NOT NULL, ADD return_date DATE DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, ADD book_id INT NOT NULL, ADD reader_id INT NOT NULL');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D0316A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE loan ADD CONSTRAINT FK_C5D30D031717D737 FOREIGN KEY (reader_id) REFERENCES reader (id)');
        $this->addSql('CREATE INDEX IDX_C5D30D0316A2B381 ON loan (book_id)');
        $this->addSql('CREATE INDEX IDX_C5D30D031717D737 ON loan (reader_id)');
        $this->addSql('ALTER TABLE reader ADD first_name VARCHAR(50) NOT NULL, ADD last_name VARCHAR(50) NOT NULL, ADD email VARCHAR(100) NOT NULL, ADD phone VARCHAR(15) DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL, ADD registration_date DATE NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CC3F893CE7927C74 ON reader (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author DROP first_name, DROP last_name, DROP biography, DROP birth_date');
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331F675F31B');
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A33112469DE2');
        $this->addSql('DROP INDEX IDX_CBE5A331F675F31B ON book');
        $this->addSql('DROP INDEX IDX_CBE5A33112469DE2 ON book');
        $this->addSql('ALTER TABLE book DROP available_copies, DROP author_id, DROP category_id');
        $this->addSql('ALTER TABLE category DROP name, DROP description');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D0316A2B381');
        $this->addSql('ALTER TABLE loan DROP FOREIGN KEY FK_C5D30D031717D737');
        $this->addSql('DROP INDEX IDX_C5D30D0316A2B381 ON loan');
        $this->addSql('DROP INDEX IDX_C5D30D031717D737 ON loan');
        $this->addSql('ALTER TABLE loan DROP loan_date, DROP due_date, DROP return_date, DROP status, DROP book_id, DROP reader_id');
        $this->addSql('DROP INDEX UNIQ_CC3F893CE7927C74 ON reader');
        $this->addSql('ALTER TABLE reader DROP first_name, DROP last_name, DROP email, DROP phone, DROP address, DROP registration_date');
    }
}
