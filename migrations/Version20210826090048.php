<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210826090048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__news AS SELECT id, name, href, description, date_add, author, image FROM news');
        $this->addSql('DROP TABLE news');
        $this->addSql('CREATE TABLE news (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description CLOB DEFAULT NULL COLLATE BINARY, author VARCHAR(255) DEFAULT NULL COLLATE BINARY, image VARCHAR(255) DEFAULT NULL COLLATE BINARY, title VARCHAR(255) NOT NULL, link VARCHAR(255) DEFAULT NULL, pub_date DATETIME NOT NULL)');
        $this->addSql('INSERT INTO news (id, title, link, description, pub_date, author, image) SELECT id, name, href, description, date_add, author, image FROM __temp__news');
        $this->addSql('DROP TABLE __temp__news');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__news AS SELECT id, title, link, description, pub_date, author, image FROM news');
        $this->addSql('DROP TABLE news');
        $this->addSql('CREATE TABLE news (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, description CLOB DEFAULT NULL, author VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL COLLATE BINARY, href VARCHAR(255) DEFAULT NULL COLLATE BINARY, date_add DATETIME NOT NULL)');
        $this->addSql('INSERT INTO news (id, name, href, description, date_add, author, image) SELECT id, title, link, description, pub_date, author, image FROM __temp__news');
        $this->addSql('DROP TABLE __temp__news');
    }
}
