mkdir -p /usr/local/share/ca-certificates/extra
openssl x509 -in scripts/certificates/rootCA.pem -out /usr/local/share/ca-certificates/extra/Sonar.crt
update-ca-certificates