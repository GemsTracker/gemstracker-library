-- This is a test table description
CREATE TABLE test__posts (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT,
	content TEXT,
	FOREIGN KEY(user_id) REFERENCES test__users(id)
);
CREATE INDEX content_idx ON test__posts(content(255));
