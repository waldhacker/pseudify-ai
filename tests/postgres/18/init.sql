DO
$do$
BEGIN
   IF EXISTS (
      SELECT FROM pg_catalog.pg_roles WHERE  rolname = 'pseudify') THEN

      RAISE NOTICE 'Role "pseudify" already exists. Skipping.';
   ELSE
      -- https://www.postgresql.org/docs/16/sql-createrole.html
      CREATE ROLE pseudify WITH LOGIN ENCRYPTED PASSWORD 'P53ud1fy(!)w4ldh4ck3r';
   END IF;
END
$do$;

-- https://www.postgresql.org/docs/16/sql-createdatabase.html
-- https://www.postgresql.org/docs/16/multibyte.html#CHARSET-TABLE
SELECT 'CREATE DATABASE pseudify_utf8 ENCODING "UTF8"' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'pseudify_utf8')\gexec

\connect pseudify_utf8;

-- https://www.postgresql.org/docs/16/ddl-schemas.html
CREATE SCHEMA IF NOT EXISTS AUTHORIZATION pseudify
