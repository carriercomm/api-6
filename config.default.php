<?php

	// Internal PEQ Database
	define("API_DB_SERVER", "localhost");
	define("API_DB_USER", "");
	define("API_DB_PASSWORD", "");
	define("API_DB_DATABASE", "peq");

	//
	// Encryption
	//

	// Directory to store encryption keys (best to place it outside of your web root.)
	define("ENCRYPT_DIR", "/path/to/private/directory/enc/");

	// Can be any string you wish, used in generating encryption keys
	define("ENCRYPT_KEY1", "PeQdBeDiToR123456");
	define("ENCRYPT_KEY2", "pEqDbEdItOr654321");
	
	// md5, gost, serpent, blowfish, loki, etc
	define("ENCRYPT_DEFAULT_ALGORITHM", "serpent");

?>