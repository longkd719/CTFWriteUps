<?php require 'config.php'; ?>

<?php
if (empty($_SESSION['username']) || empty($_SESSION['uid']) )
    die(<<<EOF
        <meta http-equiv="refresh" content="0;url=login.php" />
    EOF);

$ninjas = ["naruto", "sasuke", "kakashi", "flag"];
if ( isset($_GET["buy"]) ) {
    if (!in_array($_GET["buy"], $ninjas)) die("<aside>Product not available yet</aside>");
    $coin = $connection->query(sprintf("SELECT coin FROM coins WHERE uid=%d limit 0,1", (int)$_SESSION["uid"]))->fetch_assoc()["coin"];
    if ($_GET["buy"] === "flag") {
        if ( (int)$coin === 1337 /*(int)$coin > 1337 */  ) {        // must be 1337 :v
            $connection->query(sprintf("UPDATE coins SET coin=%d-1337 WHERE uid=%d", (int)$coin, (int)$_SESSION["uid"]));
            die("<strong>Nice brooo!! Are you a millionaire??? Here your flag: $FLAG</strong>");
        }
        else die("<strong>Try harder!!! =))) </strong>");
    }
    else {
        if ((int)$coin > 1) {
            $result = $connection->query(sprintf("UPDATE coins SET coin=%d-1 WHERE uid=%d", (int)$coin, (int)$_SESSION["uid"]));
            if ($result) die(sprintf("<strong>Buy successfully!!! Your coin: %d", (int)$coin - 1));
        }
        else die("<strong>Coin do not enough!! =(( </strong>");
    }
}
?>

<h1>Ninja Shop</h1>
<ul>
    <li>
        <img src="imgs/naruto.png" width="100px" height="100px" /> <br />
        <a href="index.php?buy=naruto">Buy naruto</a>
    </li>
    <li>
        <img src="imgs/sasuke.png" width="100px" height="100px" /> <br />
        <a href="index.php?buy=sasuke">Buy sasuke</a>
    </li>
    <li>
        <img src="imgs/kakashi.png" width="100px" height="100px" /> <br />
        <a href="index.php?buy=kakashi">Buy kakashi</a>
    </li>
    <li>
        <img src="imgs/flag.png" width="100px" height="100px" /> <br />
        <a href="index.php?buy=flag">Buy flag</a>
    </li>
</ul>