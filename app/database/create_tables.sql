CREATE TABLE IF NOT EXISTS vb_korisnici (
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	korisnicko_ime VARCHAR(20) NOT NULL,
	lozinka VARCHAR(255) NOT NULL,
	pocetni_kapital FLOAT NOT NULL,
	kapital FLOAT NOT NULL,
	zarada_od_dividendi FLOAT NOT NULL,
	rang INT NOT NULL
);

CREATE TABLE IF NOT EXISTS vb_transakcije (
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	korisnik_id INT NOT NULL,
	tip VARCHAR(10) NOT NULL,
	oznaka_dionice VARCHAR(10) NOT NULL,
	kolicina FLOAT NOT NULL,
	vrijednost FLOAT NOT NULL,
	vrijeme DATETIME NOT NULL/*,
	FOREIGN KEY (korisnik_id) REFERENCES vb_korisnici(id),
	FOREIGN KEY (oznaka_dionice) REFERENCES vb_dionice(oznaka)*/
);

CREATE TABLE IF NOT EXISTS vb_dionice (
	oznaka VARCHAR(10) NOT NULL PRIMARY KEY,
	ime VARCHAR(20) NOT NULL,
	opis TEXT,
	dividenda FLOAT,
	postavljac_dividende INT,
	vrijeme_postavljanja_dividende DATETIME/*,
	FOREIGN KEY (postavljac_dividende) REFERENCES vb_administratori(id)*/
);

CREATE TABLE IF NOT EXISTS vb_portfelji (
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	korisnik_id INT NOT NULL,
	oznaka_dionice VARCHAR(10) NOT NULL,
	kolicina FLOAT NOT NULL/*,
	FOREIGN KEY (korisnik_id) REFERENCES vb_korisnici(id),
	FOREIGN KEY (oznaka_dionice) REFERENCES vb_dionice(oznaka)*/
);

CREATE TABLE IF NOT EXISTS vb_administratori (
	id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
	korisnicko_ime VARCHAR(20) NOT NULL,
	lozinka VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS vb_postavke (
	pocetni_kapital FLOAT NOT NULL,
	komisija FLOAT NOT NULL,
	vrijeme_rang_liste DATETIME NOT NULL
);
