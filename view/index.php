<?php

include 'autentifikacija_db.php';


if(isset($_POST['login-form'])) {
    process_login();
} else if(isset($_POST['signup-form'])) {
    process_signup();
} else draw_aut_page_default();


function process_signup() {
    $username = $_POST['username'];
    $password = $_POST['password'];

    //hashiranje lozinke
    $hash_password = password_hash($password, PASSWORD_DEFAULT);
    //spoji se na bazu podataka
    $db = DB::getConnection();

    $st = $db->prepare( 'INSERT INTO Users (username, password_hash) VALUES (:user, :pass)' );
    $st->execute( [ 'user' => $username, 'pass' => $hash_password ] );

    if($st->rowCount() !== 1) draw_aut_page_signup_failed();
    return;
}


function process_login() {
    $username = $_POST['username'];
    $password = $_POST['password'];

    //hashiranje lozinke
    $hash_password = password_hash($password, PASSWORD_DEFAULT);

    $db = DB::getConnection();

    $st = $db->prepare( 'SELECT username, password_hash FROM Users WHERE username=:user' );
    $st->execute( [ 'user' => $username ] );

    if($st->rowCount() !== 1) {
        draw_aut_page_login_failed();
        return;
    }

    $row = $st->fetch();

    if(password_verify($password, $row['password_hash'])) {
        //uspjesno je logirano
        return;
    }

    else {
        draw_aut_page_login_failed();
        return;
    }
}

function draw_aut_page_default() {
    ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Registracija</title>
            <link rel="stylesheet" href="autentifikacija.css">
        </head>

        <body>

            <div class="body">
                <div class="box">
                    <div class="header">
                        <h1>Virtual<span>Stock</span></h1>
                    </div>
                    
                    <div class="login">
                        <form action="index.php" method="post">
                            <input type="text" placeholder="username" name="username"><br>
                            <input type="password" placeholder="password" name="password"><br>
                            <input type="submit" id="login-form" name="login-form" value="Log in">
                            <input type="submit" id="signup-form" name="signup-form" value="Sign up">
                        </form>
                    </div>
                </div>
            </div>


        </body>

        </html>
    <?php
}

function draw_aut_page_login_failed() {
    ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Registracija</title>
            <link rel="stylesheet" href="autentifikacija.css">
        </head>
    
        <body>
    
            <div class="body">
                <div class="box">
                    <div class="header">
                        <h1>Virtual<span>Stock</span></h1>
                    </div>
                    
                    <div class="login">
                        <form action="index.php" method="post">
                            <input type="text" placeholder="username" name="username"><br>
                            <input type="password" placeholder="password" name="password"><br>
                            <p>Username or password incorrect.</p>
                            <input type="submit" id="login-form" name="login-form" value="Log in">
                            <input type="submit" id="signup-form" name="signup-form" value="Sign up">
                        </form>
                    </div>
                </div>
            </div>
    
    
        </body>
    
        </html>
    <?php
}


function draw_aut_page_signup_failed() {
    ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Registracija</title>
            <link rel="stylesheet" href="autentifikacija.css">
        </head>
    
        <body>
    
            <div class="body">
                <div class="box">
                    <div class="header">
                        <h1>Virtual<span>Stock</span></h1>
                    </div>
                    
                    <div class="login">
                        <form action="index.php" method="post">
                            <input type="text" placeholder="username" name="username"><br>
                            <input type="password" placeholder="password" name="password"><br>
                            <p>Registration was not possible.</p>
                            <input type="submit" id="login-form" name="login-form" value="Log in">
                            <input type="submit" id="signup-form" name="signup-form" value="Sign up">
                        </form>
                    </div>
                </div>
            </div>
    
    
        </body>
    
        </html>
    <?php
    }
    
?>