<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * T-02 — Migración inicial: seis tablas, FK, índices únicos y las cinco categorías sembradas.
 *
 * DDL crudo a propósito: data-model.md §3 exige tipos `uuid`/`timestamptz`,
 * ninguna columna con DEFAULT y restricciones con nombres exactos (ck_*).
 * El builder de Schema de Laravel genera char(36) para uuid y no nombra CHECKs.
 *
 * Ver ADR-001: este archivo es el ÚNICO dueño del esquema. `infra` no contiene DDL.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');

        DB::statement(<<<'SQL'
            CREATE TABLE library.category (
                id uuid NOT NULL,
                name varchar(120) NOT NULL,
                CONSTRAINT category_pkey PRIMARY KEY (id),
                CONSTRAINT uq_category_name UNIQUE (name)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE library."user" (
                id uuid NOT NULL,
                username varchar(120) NOT NULL,
                password_hash varchar(512) NOT NULL,
                role varchar(40) NOT NULL,
                CONSTRAINT user_pkey PRIMARY KEY (id),
                CONSTRAINT uq_user_username UNIQUE (username),
                CONSTRAINT ck_user_role CHECK (role IN ('admin', 'librarian'))
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE library.member (
                id uuid NOT NULL,
                full_name varchar(200) NOT NULL,
                document_number varchar(30) NOT NULL,
                CONSTRAINT member_pkey PRIMARY KEY (id),
                CONSTRAINT uq_member_document_number UNIQUE (document_number)
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE library.book (
                id uuid NOT NULL,
                title varchar(250) NOT NULL,
                author varchar(200) NOT NULL,
                isbn char(13) NOT NULL,
                category_id uuid NOT NULL,
                total_copies integer NOT NULL,
                available_copies integer NOT NULL,
                cover_key varchar(512) NULL,
                deleted_at timestamptz NULL,
                CONSTRAINT book_pkey PRIMARY KEY (id),
                CONSTRAINT fk_book_category FOREIGN KEY (category_id)
                    REFERENCES library.category (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT ck_book_total_positive CHECK (total_copies >= 1),
                CONSTRAINT ck_book_available_range CHECK (available_copies >= 0 AND available_copies <= total_copies)
            )
        SQL);

        // ISBN único SOLO entre libros activos (índice parcial) — data-model §4 / ADR-003.
        DB::statement('CREATE UNIQUE INDEX uq_book_isbn_active ON library.book (isbn) WHERE deleted_at IS NULL');

        DB::statement(<<<'SQL'
            CREATE TABLE library.loan (
                id uuid NOT NULL,
                member_id uuid NOT NULL,
                loaned_by_user_id uuid NOT NULL,
                loaned_at timestamptz NOT NULL,
                CONSTRAINT loan_pkey PRIMARY KEY (id),
                CONSTRAINT fk_loan_member FOREIGN KEY (member_id)
                    REFERENCES library.member (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT fk_loan_user FOREIGN KEY (loaned_by_user_id)
                    REFERENCES library."user" (id) ON DELETE RESTRICT ON UPDATE NO ACTION
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE library.loan_item (
                id uuid NOT NULL,
                loan_id uuid NOT NULL,
                book_id uuid NOT NULL,
                book_title varchar(250) NOT NULL,
                isbn char(13) NOT NULL,
                category_name varchar(120) NOT NULL,
                due_at timestamptz NOT NULL,
                returned_at timestamptz NULL,
                CONSTRAINT loan_item_pkey PRIMARY KEY (id),
                CONSTRAINT fk_loan_item_loan FOREIGN KEY (loan_id)
                    REFERENCES library.loan (id) ON DELETE CASCADE ON UPDATE NO ACTION,
                CONSTRAINT fk_loan_item_book FOREIGN KEY (book_id)
                    REFERENCES library.book (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
                CONSTRAINT uq_loan_item_loan_book UNIQUE (loan_id, book_id)
            )
        SQL);

        // Índices de acceso — data-model §4.
        DB::statement('CREATE INDEX book_category_id_title_idx ON library.book (category_id, title) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX loan_loaned_at_idx ON library.loan (loaned_at)');
        DB::statement('CREATE INDEX loan_item_book_id_idx ON library.loan_item (book_id)');
        DB::statement('CREATE INDEX loan_item_due_at_idx ON library.loan_item (due_at) WHERE returned_at IS NULL');

        // Semilla: cinco categorías con identificadores literales — data-model §9.
        DB::statement(<<<'SQL'
            INSERT INTO library.category (id, name) VALUES
                ('11111111-1111-4111-8111-111111111111', 'Novela'),
                ('22222222-2222-4222-8222-222222222222', 'Ciencia'),
                ('33333333-3333-4333-8333-333333333333', 'Tecnología'),
                ('44444444-4444-4444-8444-444444444444', 'Historia'),
                ('55555555-5555-4555-8555-555555555555', 'Infantil')
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
    }
};
