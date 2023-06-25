<?php require 'config.php'; ?>

<?php
    if (!empty($_SESSION['username']) && !empty($_SESSION['uid']) )
        header('Location: index.php');

        if ( $_SERVER['REQUEST_METHOD'] !== "POST" )
        die(<<<EOF
            <title>Login</title>
            <body>
                <h1>Login form</h1>
                <form action="login.php" method="POST">
                    <label for="username">Username: </label>
                    <input type="text" name="username">
                    <br>
                    <label for="password">Password: </label>
                    <input type="text" name="password">
                    <br>
                    <input type="submit" value="Submit" />
                </form>
                <br />
                
                <a href="register.php">No account???</a>
            </body>
            </html>
            EOF
        );
    
    foreach (array("username", "password") as $value) {
        if (empty($_POST[$value])) die("<aside>Missing parameter!</aside>");
        waf($_POST[$value]);
    }

    if (strlen($_POST["username"]) > 26) die("<aside>Username too long<aside>");
    $result = $connection->query(sprintf('SELECT * FROM users WHERE username="%s" AND password="%s" limit 0,1', $_POST["username"], md5($_POST["password"])))->fetch_assoc();
    if(empty($result))
        die(<<<EOF
            <strong>Username or password is wrong</strong>
            <meta http-equiv="refresh" content="1;url=login.php" />
        EOF);
    else {
        $_SESSION["username"] = $result['username'];
        $_SESSION["uid"] = $result['uid'];
        die(<<<EOF
            <strong>Welcome brooo!! Buy flag with me !!!! </strong>
            <meta http-equiv="refresh" content="1;url=index.php" />
        EOF);
    }

?>
