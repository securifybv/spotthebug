CREATE TABLE accounts
	(ID 		INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
	user_id 	INT 	NOT NULL,
	token		TEXT 	UNIQUE NOT NULL,
	currency	TEXT    NOT NULL,
	amount		INT     NOT NULL check(amount >= 0),
	address		TEXT    UNIQUE NOT NULL);