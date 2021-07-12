<?php

if(! isset($_SESSION)) { 
	session_start(); 
}

require_once __DIR__ . '/../app/debug.php';



class KorisnikController {
	
	public function index() {
		$username = $this->checkLogin();
		if ($username) {
			//$css_link = 'view/korisnik.css';
			require_once __DIR__ . '/../view/korisnik_index.php';
		} else {
			$this->gotoLogin();
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
	
	public function gotoLogin() {
		header('Location: index.php?rt=login');
	}
	
	public function logout() {
		session_unset();
		session_destroy();
		$this->gotoLogin();
	}
	
}