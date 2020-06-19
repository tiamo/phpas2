
Generate a couple private and public key: 

```bash
openssl req -newkey rsa:2048 -sha1 -nodes -keyout private.pem -x509 -days 1095 -out server.pub -subj "/C=US/ST=MyDept/L=m=MyCity/O=myCompany/OU=IT/CN=mydomain.com"
```

Extract a pkcs7 cert file:

```bash
openssl crl2pkcs7 -nocrl -certfile server.pub -out cert.p7b
```

Extract a smine cert file from pkcs7:

```bash
openssl pkcs7 -in cert.p7b -out smime-cert.pem -print_certs
```

Merge them into a p12 file:

```bash
openssl pkcs12 -inkey cert.p7b -in smime-cert.pem -inkey private.pem -in server.pub -export -out server.p12 -nodes -passout pass:
```

Check your p12 file:

```bash
openssl pkcs12 -in server.p12 -noout -info
```
