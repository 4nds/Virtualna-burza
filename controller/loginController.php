<?php

if(! isset($_SESSION)) { 
	session_start(); 
}

//require_once __DIR__ . '/../model/book.class.php';
require_once __DIR__ . '/../app/database/db.class.php';
require_once __DIR__ . '/../app/debug.php';
require_once __DIR__ . '/../model/korisnik.class.php';
require_once __DIR__ . '/../model/administrator.class.php';



class LoginController {
	
	public function index() {
		$username = $this->checkLogin();
		if ($username) {
			$this->gotoKorisnik($username);
		} else {
			$css_link = 'view/login.css';
			require_once __DIR__ . '/../view/login_index.php';
		}
	}
	
	protected function checkLogin() {
		if (isset($_SESSION['login_hash'])) {
			$login_hash = $_SESSION['login_hash'];
			list($username, $cookie_hash) = explode(',', $_SESSION['login_hash']);
			$SECRET_WORD = 'Računarski praktikum 2 - Virtualna burza';
			if (md5($username . $SECRET_WORD) === $cookie_hash) {
				return $username;
			}
		}
		return false;
	}
	
	public function loginFailed() {
		$css_link = 'view/login.css';
		$error_message  = 'Korisničko ime ili lozinka nisu ispravni.';
		require_once __DIR__ . '/../view/login_failed.php';
	}
	
	public function signupFailed() {
		$css_link = 'view/login.css';
		$error_message  = 'Registracija nije bila uspješna.';
		require_once __DIR__ . '/../view/login_failed.php';
	}
	
	public function signupFailedUsernameTaken() {
		$css_link = 'view/login.css';
		$error_message  = 'Uneseno korisničko ime već postoji.';
		require_once __DIR__ . '/../view/login_failed.php';
	}
	
	
	public function checkForm() {
		if(isset($_POST['login-form'])) {
    		$this->processLogin();
		} else if(isset($_POST['signup-form'])) {
    		$this->processSignup();
		}
	}
	
	private function processLogin() {
		$username = $_POST['korisnicko_ime'];
		$password = $_POST['lozinka'];
		$hash_password = password_hash($password, PASSWORD_DEFAULT);    // hashiranje lozinke
		$korisnici = Korisnik::where('korisnicko_ime', $username, ['limit' => 1]);
		$database_hash_password = '';
		if (count($korisnici) > 0) {
			$database_hash_password = $korisnici[0]->lozinka;
		}
		if(password_verify($password, $database_hash_password)) {
			$this->gotoKorisnik($username);
		} else {
			$this->loginFailed();
		}
	}
	
	private function processSignup() {
		$username = $_POST['korisnicko_ime'];
		$password = $_POST['lozinka'];
		$hash_password = password_hash($password, PASSWORD_DEFAULT);    // hashiranje lozinke	
		$korisnici = Korisnik::where('korisnicko_ime', $username, ['limit' => 1]);
		if (count($korisnici) > 0) {
			$this->signupFailedUsernameTaken();
			return;
		}
		$administratori = Administrator::all([
			'order by' => 'vrijeme_postavljanja_kapitala',
			'limit' => 1
		]);
		$početni_kapital = Administrator::$DEFAULT_POCETNI_KAPITAL;
		if (count($administratori) > 0) {
			$početni_kapital = $administratori[0]->pocetni_kapital;
		}				
		$korisnik = new Korisnik([
			'korisnicko_ime' => $username,
			'lozinka' => $hash_password,
			'kapital' => $početni_kapital,
			'pocetni_kapital' => $početni_kapital,
			'zarada_od_dividendi' => 0,
		]);
		$inserted = $korisnik->save();
		if ($inserted) {
			$this->gotoKorisnik($username);
			return;
		}		
		$this->signupFailed();
	}
	
	public function gotoKorisnik($username) {
		$SECRET_WORD = 'Računarski praktikum 2 - Virtualna burza';
		$_SESSION['login_hash'] = $username . ',' . md5($username . $SECRET_WORD);
		header('Location: index.php?rt=korisnik');
	}
	
	
}

?>