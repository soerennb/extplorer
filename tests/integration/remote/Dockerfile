FROM debian:trixie
RUN apt-get update && apt-get install -y --no-install-recommends openssh-server python3 python3-pyftpdlib python3-openssl openssl netcat-openbsd \
    && rm -rf /var/lib/apt/lists/* \
    && useradd -m -d /home/fixture -s /bin/sh fixture \
    && echo 'fixture:fixture-password' | chpasswd \
    && mkdir -p /home/fixture/data /etc/ssh /run/sshd \
    && chown fixture:fixture /home/fixture/data \
    && ssh-keygen -A \
    && mkdir /home/fixture/.ssh \
    && ssh-keygen -t ed25519 -N 'fixture-key-passphrase' -f /cert-fixture-key \
    && cp /cert-fixture-key.pub /home/fixture/.ssh/authorized_keys \
    && chown -R fixture:fixture /home/fixture/.ssh \
    && chmod 700 /home/fixture/.ssh \
    && chmod 600 /home/fixture/.ssh/authorized_keys \
    && mkdir /certificates \
    && openssl req -x509 -newkey rsa:2048 -nodes -days 2 -subj '/CN=eXtplorer test CA' -keyout /certificates/ca.key -out /certificates/ca.pem \
    && openssl req -newkey rsa:2048 -nodes -subj '/CN=remote.fixture' -keyout /certificates/server.key -out /certificates/server.csr \
    && printf 'subjectAltName=DNS:remote.fixture\nextendedKeyUsage=serverAuth\n' > /certificates/server.ext \
    && openssl x509 -req -days 2 -in /certificates/server.csr -CA /certificates/ca.pem -CAkey /certificates/ca.key -CAcreateserial -extfile /certificates/server.ext -out /certificates/server.pem
COPY server.py /server.py
RUN printf 'PasswordAuthentication yes\nPermitRootLogin no\nHostKey /etc/ssh/ssh_host_ed25519_key\nSubsystem sftp internal-sftp\n' > /etc/ssh/sshd_config
CMD ["sh", "-c", "/usr/sbin/sshd -D -e & exec python3 /server.py"]
