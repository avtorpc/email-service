<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260519200124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Email ingestion audit log + processing state';
    }

    public function up(Schema $schema): void
    {
        // =========================================================
        // RAW EVENT LOG
        // immutable append-only transport audit journal
        // =========================================================

        $this->addSql(<<<SQL
CREATE TABLE email.raw_event_log
(
    id BIGSERIAL PRIMARY KEY,

    event_id VARCHAR(36) NOT NULL,

    event_type VARCHAR(100),

    event_version INT DEFAULT 1,

    occurred_at TIMESTAMP WITH TIME ZONE,

    trace_id VARCHAR(36),

    correlation_id VARCHAR(100),

    producer_service VARCHAR(100),

    callback TEXT,

    recipient_email TEXT NOT NULL,

    recipient_name VARCHAR(255),

    template VARCHAR(255),

    locale VARCHAR(20),

    payload JSONB NOT NULL,

    duplicate BOOLEAN NOT NULL DEFAULT FALSE,

    created_at TIMESTAMP NOT NULL DEFAULT NOW()
)
SQL);

        // =========================================================
        // COMMENTS — RAW EVENT LOG
        // =========================================================

        $this->addSql("
COMMENT ON TABLE email.raw_event_log
IS 'Immutable append-only audit log всех входящих email событий включая retry и duplicate delivery'
");

        $this->addSql("
COMMENT ON COLUMN email.raw_event_log.event_id
IS 'Бизнес идентификатор события. Может повторяться при повторной доставке'
");

        $this->addSql("
COMMENT ON COLUMN email.raw_event_log.recipient_email
IS 'Email адрес получателя'
");

        $this->addSql("
COMMENT ON COLUMN email.raw_event_log.payload
IS 'Полный payload входящего события'
");

        $this->addSql("
COMMENT ON COLUMN email.raw_event_log.duplicate
IS 'Флаг повторной доставки события'
");

        // =========================================================
        // INDEXES — RAW EVENT LOG
        // =========================================================

        $this->addSql("
CREATE INDEX idx_raw_event_log_event_id
ON email.raw_event_log (event_id)
");

        $this->addSql("
CREATE INDEX idx_raw_event_log_recipient_email
ON email.raw_event_log (recipient_email)
");

        // =========================================================
        // EMAIL PROCESSING LOG
        // operational processing state + idempotency
        // =========================================================

        $this->addSql(<<<SQL
CREATE TABLE email.email_processing_log
(
    id BIGSERIAL PRIMARY KEY,

    event_id VARCHAR(36) NOT NULL,

    event_type VARCHAR(100),

    event_version INT DEFAULT 1,

    occurred_at TIMESTAMP WITH TIME ZONE,

    trace_id VARCHAR(36),

    correlation_id VARCHAR(100),

    producer_service VARCHAR(100),

    callback TEXT,

    recipient_email TEXT NOT NULL,

    recipient_name VARCHAR(255),

    template VARCHAR(255),

    locale VARCHAR(20),

    payload JSONB NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'queued',

    attempt_count INT NOT NULL DEFAULT 0,

    last_error TEXT,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),

    updated_at TIMESTAMP,

    sent_at TIMESTAMP,

    failed_at TIMESTAMP,

    expired_at TIMESTAMP,

    CONSTRAINT uniq_email_processing_log_event_id
        UNIQUE (event_id)
)
SQL);

        // =========================================================
        // COMMENTS — PROCESSING LOG
        // =========================================================

        $this->addSql("
COMMENT ON TABLE email.email_processing_log
IS 'Operational processing state table для email pipeline'
");

        $this->addSql("
COMMENT ON COLUMN email.email_processing_log.event_id
IS 'Идемпотентный ключ события'
");

        $this->addSql("
COMMENT ON COLUMN email.email_processing_log.recipient_email
IS 'Email получателя'
");

        $this->addSql("
COMMENT ON COLUMN email.email_processing_log.status
IS 'Статус обработки: queued, processing, sent, failed'
");

        $this->addSql("
COMMENT ON COLUMN email.email_processing_log.payload
IS 'Payload необходимый для runtime обработки email'
");

        // =========================================================
        // INDEXES — PROCESSING LOG
        // =========================================================

        $this->addSql("
CREATE INDEX idx_email_processing_log_recipient_email
ON email.email_processing_log (recipient_email)
");
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'DROP TABLE IF EXISTS email.email_processing_log'
        );

        $this->addSql(
            'DROP TABLE IF EXISTS email.raw_event_log'
        );
    }
}
