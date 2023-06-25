<?php require 'config.php'; ?>

<h1>Profile</h1>
<?php
    if (empty($_SESSION['username']) || empty($_SESSION['uid']) )
        die(<<<EOF
            <meta http-equiv="refresh" content="0;url=login.php" />
        EOF);
    
    $fullname = $connection->query(sprintf("SELECT fullname FROM users WHERE username='%s' limit 0,1", $_SESSION["username"]));
    if (gettype($fullname) !== "boolean") echo "<h2>Hello: ". $fullname->fetch_assoc()["fullname"] ."</h2>";
    else echo "<h2>Hello: Anonymous </h2>";
    $coin = $connection->query(sprintf("SELECT * FROM coins WHERE uid=%d limit 0,1", (int)$_SESSION["uid"]))->fetch_assoc()["coin"];
    echo <<<EOF
        <h2>Your coin: $coin</h2>
    EOF;

    // Ran out of money?? No need to worry, you can reset carefree!! But only limited from 1-99 coins =)))
    if ( isset($_GET["new_balance"]) and waf($_GET["new_balance"]) ) {
        if (strlen($_GET["new_balance"]) > 2) die("<strong>Only allow from 1 to 99</strong>");
        else {
            $result = $connection->query(sprintf("UPDATE coins SET coin=%s WHERE uid=%d", $_GET["new_balance"], (int) $_SESSION['uid']));
            if ($result) die("<strong>Your coin has been updated</strong>");
            else die("<strong>0ops!!! Coin update has failed</strong>");
        }
    } 
?>