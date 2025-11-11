<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial database schema migration for 10x-cards application.
 * Creates tables: user, flashcard, flashcard_generation, repetition_record
 */
final class Version20251111212844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for 10x-cards: user, flashcard, flashcard_generation, repetition_record tables';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL DEFAULT \'[]\',
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_USER_EMAIL ON users (email)');

        // Create flashcard_generation table (before flashcard due to foreign key)
        $this->addSql('
            CREATE TABLE flashcard_generation (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                source_text TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'pending\',
                generated_at TIMESTAMP(0) WITHOUT TIME ZONE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_generation_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ');

        $this->addSql('CREATE INDEX IDX_FLASHCARD_GENERATION_USER_ID ON flashcard_generation (user_id)');
        $this->addSql('CREATE INDEX IDX_FLASHCARD_GENERATION_STATUS ON flashcard_generation (status)');
        $this->addSql('CREATE INDEX IDX_FLASHCARD_GENERATION_CREATED_AT ON flashcard_generation (created_at)');

        // Add check constraint for status enum
        $this->addSql('
            ALTER TABLE flashcard_generation 
            ADD CONSTRAINT check_flashcard_generation_status 
            CHECK (status IN (\'pending\', \'completed\', \'failed\'))
        ');

        // Create flashcard table
        $this->addSql('
            CREATE TABLE flashcard (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NOT NULL,
                question TEXT NOT NULL,
                answer TEXT NOT NULL,
                source VARCHAR(10) NOT NULL DEFAULT \'manual\',
                generation_id INTEGER,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_flashcard_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT fk_flashcard_generation FOREIGN KEY (generation_id) REFERENCES flashcard_generation (id) ON DELETE SET NULL
            )
        ');

        $this->addSql('CREATE INDEX IDX_FLASHCARD_USER_ID ON flashcard (user_id)');
        $this->addSql('CREATE INDEX IDX_FLASHCARD_CREATED_AT ON flashcard (created_at)');
        $this->addSql('CREATE INDEX IDX_FLASHCARD_GENERATION_ID ON flashcard (generation_id)');

        // Add check constraint for source enum
        $this->addSql('
            ALTER TABLE flashcard 
            ADD CONSTRAINT check_flashcard_source 
            CHECK (source IN (\'ai\', \'manual\'))
        ');

        // Create repetition_record table
        $this->addSql('
            CREATE TABLE repetition_record (
                id SERIAL PRIMARY KEY,
                flashcard_id INTEGER NOT NULL,
                last_reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE,
                next_review_at TIMESTAMP(0) WITHOUT TIME ZONE,
                ease_factor NUMERIC(5,2) NOT NULL DEFAULT 2.50,
                interval_days INTEGER NOT NULL DEFAULT 1,
                repetition_count INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_repetition_flashcard FOREIGN KEY (flashcard_id) REFERENCES flashcard (id) ON DELETE CASCADE
            )
        ');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_REPETITION_FLASHCARD_ID ON repetition_record (flashcard_id)');
        $this->addSql('CREATE INDEX IDX_REPETITION_NEXT_REVIEW_AT ON repetition_record (next_review_at)');
        $this->addSql('CREATE INDEX IDX_REPETITION_LAST_REVIEWED_AT ON repetition_record (last_reviewed_at)');

        // Create function to update updated_at timestamp (PostgreSQL specific)
        $this->addSql('
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            $$ language \'plpgsql\'
        ');

        // Create triggers for updated_at columns
        $this->addSql('
            CREATE TRIGGER update_user_updated_at 
            BEFORE UPDATE ON users 
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()
        ');

        $this->addSql('
            CREATE TRIGGER update_flashcard_updated_at 
            BEFORE UPDATE ON flashcard 
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()
        ');

        $this->addSql('
            CREATE TRIGGER update_repetition_record_updated_at 
            BEFORE UPDATE ON repetition_record 
            FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()
        ');
    }

    public function down(Schema $schema): void
    {
        // Drop triggers
        $this->addSql('DROP TRIGGER IF EXISTS update_repetition_record_updated_at ON repetition_record');
        $this->addSql('DROP TRIGGER IF EXISTS update_flashcard_updated_at ON flashcard');
        $this->addSql('DROP TRIGGER IF EXISTS update_user_updated_at ON users');

        // Drop function
        $this->addSql('DROP FUNCTION IF EXISTS update_updated_at_column()');

        // Drop tables in reverse order (respecting foreign keys)
        $this->addSql('DROP TABLE IF EXISTS repetition_record');
        $this->addSql('DROP TABLE IF EXISTS flashcard');
        $this->addSql('DROP TABLE IF EXISTS flashcard_generation');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}

