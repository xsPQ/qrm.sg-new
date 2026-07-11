# qrm.sg backup runner image.
#
# Contains everything needed to run the daily encrypted backup pipeline and the
# weekly restore test against the PostgreSQL service:
#   - postgresql-client (pg_dump / pg_restore / psql)
#   - gnupg (symmetric AES256 encryption)
#   - aws-cli v2 (S3-compatible external copy)
#   - supercronic (cron without root)
#   - tini (PID 1 / signal handling)
#
# Build:
#   docker build -t qrm-backup:latest -f docker/backup/Dockerfile docker/backup

FROM debian:bookworm-slim

ARG POSTGRES_MAJOR=16
ARG SUPERCRONIC_VERSION=v0.2.30
ARG SUPERCRONIC_SHA1SUM=9f27ad28c5c57cd133325b2a66bba69ba2235799
ENV DEBIAN_FRONTEND=noninteractive
ENV BACKUP_LOCAL_DIR=/backups \
    BACKUP_LOG_DIR=/var/log/backup \
    PG_VERSION="${POSTGRES_MAJOR}"

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates curl gnupg gzip jq tini \
      "postgresql-client-${POSTGRES_MAJOR}" \
 && curl -fsSL "https://github.com/aptible/supercronic/releases/download/${SUPERCRONIC_VERSION}/supercronic-linux-amd64" \
      -o /usr/local/bin/supercronic \
 && echo "${SUPERCRONIC_SHA1SUM}  /usr/local/bin/supercronic" | sha1sum -c - \
 && chmod +x /usr/local/bin/supercronic \
 && curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o /tmp/aws.zip \
 && apt-get install -y --no-install-recommends unzip \
 && unzip -q /tmp/aws.zip -d /tmp && /tmp/aws/install \
 && rm -rf /tmp/aws* \
 && apt-get purge -y --auto-remove unzip \
 && rm -rf /var/lib/apt/lists/* \
 && mkdir -p /backups /var/log/backup /etc/cron.d

# Pipelines
COPY bin/lib/common.sh   /usr/local/lib/backup/common.sh
COPY bin/backup.sh       /usr/local/bin/backup.sh
COPY bin/restore.sh      /usr/local/bin/restore.sh
COPY bin/restore-test.sh /usr/local/bin/restore-test.sh
COPY bin/healthcheck.sh  /usr/local/bin/healthcheck.sh
RUN chmod 0755 /usr/local/bin/backup.sh /usr/local/bin/restore.sh \
               /usr/local/bin/restore-test.sh /usr/local/bin/healthcheck.sh

# Schedule
COPY crontab /etc/cron.d/qrm-backup

# Ensure the lib is sourced by fixing the path expectation (bin/lib/common.sh).
RUN mkdir -p /usr/local/lib/backup

WORKDIR /backups
VOLUME ["/backups", "/var/log/backup"]

# supercronic runs as PID-2 under tini. Pass BACKUP_* env at runtime.
HEALTHCHECK --interval=5m --timeout=10s --start-period=24h --retries=2 \
  CMD /usr/local/bin/healthcheck.sh || exit 1

ENTRYPOINT ["/usr/bin/tini", "--"]
CMD ["supercronic", "-passthrough-logs", "/etc/cron.d/qrm-backup"]
