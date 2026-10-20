#!/bin/sh
set -eu
cd "$(dirname "$0")/../../.."
docker run --rm --network none --entrypoint sh \
    -v "$(pwd)/tests/integration/mail:/fixtures:ro" \
    -e EXTPLORER_WRITE_PATH=/tmp/mail-fixture-state \
    -e EXTPLORER_SENDMAIL_PATH='/opt/extplorer-mail-fixture/sendmail ; echo QUOTE_BROKEN' \
    -e EXTPLORER_MAIL_CAPTURE=/tmp/mail-fixture-state/runtime/capture \
    "${EXTPLORER_TEST_IMAGE:-extplorer3:security-mail}" -ec '
        mkdir -p /opt/extplorer-mail-fixture
        printf "#!/bin/sh\ncat > \"\$EXTPLORER_MAIL_CAPTURE\"\n" > "$EXTPLORER_SENDMAIL_PATH"
        chmod 0755 "$EXTPLORER_SENDMAIL_PATH"
        mkdir -p /tmp/mail-fixture-state
        for directory in config logs runtime file_manager_root cache; do
            mkdir -p "/tmp/mail-fixture-state/$directory"
        done
        chown -R www-data:www-data /tmp/mail-fixture-state
        exec su-exec www-data php /fixtures/check.php
    '
