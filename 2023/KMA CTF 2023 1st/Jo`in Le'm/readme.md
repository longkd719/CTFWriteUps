# Jo`in Le'm

![image-20230619172618157](./assets/image-20230619172618157.png)

> :warning:
>
> Hint 1: Kho^ng ca\n RCEEEeeeeeee!'
>
> Hint 2: docker? df -h

Truy cập trang web là mình nhận được source code

![image-20230619173803696](./assets/image-20230619173803696.png)

Format lại và decode các chuỗi đã encode trong mã cho dễ nhìn :D

```php
<?php
goto TwWdv;
kP1Xc:
if ($url["scheme"] !== "http" && $url["scheme"] !== "https") {
    die;
}
goto dD_At;
B10vf:
function curl($url)
{
    $ch = curl_init($url);
    _:
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $ua = "User-Agent: " . ($_GET["ua"] ?? "vinhjaxt/1.0 (Mô vina Zai Zóc Vơ sần 69.0.1 nhanh tuyệt cú mèo chấn động năm châu, tương thích oép 5 CHẤM 0, nhanh đóng băng hỏa diệm sơn, với tốc độ ánh sáng bờ nốc chên)");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array($ua));
    curl_setopt($ch, CURLOPT_URL, $url);
    $d = curl_exec($ch);
    $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $url = $redirect_url;
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode >= 300 && $httpcode < 400 && $redirect_url) {
        goto _;
    }
    curl_close($ch);
    return $d;
}
goto zUkQD;
SZSj0:
_:
goto WuY9_;
RzWUE:
$ch = curl_init();
goto YlbNQ;
dD_At:
if ($url["host"] === "127.0.0.1" || gethostbyname($url["host"]) === "127.0.0.1") {
    die;
}
goto RzWUE;
TwWdv:
show_source(__FILE__);
goto B10vf;
zUkQD:
$url = parse_url($_GET["url"]);
goto kP1Xc;
YlbNQ:
if (curl_escape($ch, $_GET["url"]) === urlencode($_GET["url"])) {
    die;
}
goto SZSj0;
WuY9_:
echo curl($_GET["url"]);
```

Đại loại là bài này sử dụng `curl` đến một `url` được lấy từ param `$_GET["url"]`, trước khi curl thì url của mình có qua các bước kiểm tra đầu vào

```php
if ($url["scheme"] !== "http" && $url["scheme"] !== "https") {
    die;
}
if ($url["host"] === "127.0.0.1" || gethostbyname($url["host"]) === "127.0.0.1") {
    die;
}
if (curl_escape($ch, $_GET["url"]) === urlencode($_GET["url"])) {
    die;
}
```

Mình cần đảm bảo các điều kiện này để chương trình có thể curl đến url của mình. Mình có tìm hiểu về sự khác nhau của `curl_escape` và `urlencode`, cả 2 đều có mục đích là url encode nhưng mà 2 cái này khác nhau ở chỗ `curl_escape` thì không encode một vài kí tự trong đó có dấu `+`. Vậy nên khi đó url của mình sẽ là 

```
http://103.163.25.143:20109/?url=http://1:1+1@9vrvdr2e.requestrepo.com
```

Kết quả curl sẽ được hiển thị trên màn hình

![image-20230621204652142](./assets/image-20230621204652142.png)

Mà trong source code có ghi là nó sẽ curl một lần nữa nếu trang web có redirect url

```php
    $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    $url = $redirect_url;
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode >= 300 && $httpcode < 400 && $redirect_url) {
        goto _;
    }
```

Thử sửa lại response redirect đến một file hệ thống

![image-20230621205201085](./assets/image-20230621205201085.png)

Kết quả thành công -> vậy nên ở challenge này là lỗi Directory Traversal, mình cần tìm flag ở một đường dẫn nào đó

![image-20230621205228308](./assets/image-20230621205228308.png)

Khi hint 2 được đưa ra thì mình dự đoán flag được mount vào container trong docker vậy nên có thể có đường dẫn flag nằm trong `/proc/mounts` của container

![image-20230621205415470](./assets/image-20230621205415470.png)

Đọc file `/proc/mounts` mình tìm được đường dẫn flag `/home/siuvip_saoanhbatduocem/etc/passwd`

![image-20230621205554608](./assets/image-20230621205554608.png)

Đọc file `/home/siuvip_saoanhbatduocem/etc/passwd` và mình có flag

![image-20230621205633736](./assets/image-20230621205633736.png)

```
Flag: KMACTF{Pha?i_the'_chu'! La`m to't le'm con troai!}
```
