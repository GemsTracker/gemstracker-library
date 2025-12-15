-- This is a test table description
CREATE TABLE test__users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	username VARCHAR(255) NOT NULL,
	email VARCHAR(255),
	UNIQUE KEY username_unique (username)
);
CREATE INDEX email_idx ON test__users(email);
