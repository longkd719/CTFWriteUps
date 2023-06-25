<?php require 'config.php'; ?>

<?php
    if (!empty($_SESSION['username']) && !empty($_SESSION['uid']) )
        header('Location: index.php');
    
    if ( $_SERVER['REQUEST_METHOD'] !== "POST" )
        die(<<<EOF
            <title>Register</title>
            <body>
                <h1>Register</h1>
                <form action="register.php" method="POST">
                    <label for="username">Username: </label>
                    <input type="text" name="username" />
                    <br>
                    <label for="fullname">Fullname: </label>
                    <input type="text" name="fullname" />
                    <br>
                    <label for="password"> Password: </label>
                    <input type="text" name="password">
                    <br>
                    <input type="submit" value="Sign up" />
                </form>
            </body>
            </html>
        EOF);

    foreach (array("username", "password", "fullname") as $value) {
        if (empty($_POST[$value])) die("<aside>Missing parameter!</aside>");
        waf($_POST[$value]);
    }
    if (strlen($_POST["username"]) > 26) die("<aside>Username too long<aside>");
    if (strlen($_POST["fullname"]) > 10) die("<aside>Fullname too long<aside>");
    
    // check account is exists
    if ( $connection->query(sprintf('SELECT * FROM users WHERE username="%s" and password="%s"', $_POST["username"], md5($_POST["password"])))->fetch_assoc()["uid"] )
        die("<strong>User is exists</strong>");

    $result = $connection->query(sprintf('INSERT INTO users(username, password, fullname) VALUES ("%s", "%s", "%s")', $_POST["username"], md5($_POST["password"]), $_POST["fullname"]));

    if ($result) {
        $uid = $connection->query(sprintf('SELECT uid FROM users WHERE username="%s" and password="%s" limit 0,1', $_POST["username"], md5($_POST["password"])))->fetch_assoc()["uid"];
        if ($uid) {
            if ($connection->query(sprintf('INSERT INTO coins(coin, uid) VALUES (100, %d)', (int)$uid)))
                die("<strong>User created successfully</strong>");
        }
    } else die("<strong>Something went wrong! Try again!</strong>");
?>