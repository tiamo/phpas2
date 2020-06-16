
Generate a couple private and public key: 

```bash
openssl req -newkey rsa:2048 -nodes -keyout privkey.pem -x509 -days 365 -out public.pem
```

Merge them into a p12 file:

```bash
openssl pkcs12 -inkey privkey.pem -in public.pem -export -out server.p12
```

Check your p12 file:

```bash
openssl pkcs12 -in server.p12 -noout -info
```
