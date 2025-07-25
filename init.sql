-- DROP SCHEMA public;
CREATE SCHEMA public AUTHORIZATION pg_database_owner;

-- DROP SEQUENCE user_balances_id_seq;
CREATE SEQUENCE user_balances_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;

-- DROP SEQUENCE users_id_seq;
CREATE SEQUENCE users_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START 1
	CACHE 1
	NO CYCLE;-- public.users definition

-- DROP TABLE users;
CREATE TABLE users (
	id serial4 NOT NULL,
	"name" varchar(255) NOT NULL,
	"token" varchar(255) NOT NULL,
	okx_api_key varchar(255) NOT NULL,
	okx_secret_key varchar(255) NOT NULL,
	okx_passphrase varchar(255) NOT NULL,
	created_at timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	is_test_user bool DEFAULT false NULL,
	CONSTRAINT users_pkey PRIMARY KEY (id),
	CONSTRAINT users_token_key UNIQUE (token)
);


-- DROP TABLE user_balances;
CREATE TABLE user_balances (
	id serial4 NOT NULL,
	user_id int4 NOT NULL,
	ccy varchar(16) NOT NULL,
	eq numeric(36, 18) NOT NULL,
	eq_usd numeric(36, 2) NOT NULL,
	rate numeric(36, 10) NULL,
	created_at timestamp DEFAULT CURRENT_TIMESTAMP NULL,
	CONSTRAINT user_balances_pkey PRIMARY KEY (id),
	CONSTRAINT user_balances_user_id_ccy_key UNIQUE (user_id, ccy),
	CONSTRAINT user_balances_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);