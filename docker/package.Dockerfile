# syntax=docker/dockerfile:1
#checkov:skip=CKV_DOCKER_2
#checkov:skip=CKV_DOCKER_3
FROM alpine

LABEL org.opencontainers.image.title=SolidInvoice
LABEL org.opencontainers.image.description="Simple and elegant invoicing solution"
LABEL org.opencontainers.image.url=https://solidinvoice.co
LABEL org.opencontainers.image.source=https://github.com/SolidInvoice/SolidInvoice
LABEL org.opencontainers.image.licenses=MIT
LABEL org.opencontainers.image.vendor="SolidWorx"

ARG SOLIDINVOICE_VERSION=''
ENV SOLIDINVOICE_VERSION=${SOLIDINVOICE_VERSION}
ENV SOLIDINVOICE_ENV=prod
ENV SOLIDINVOICE_DEBUG=0
ENV SOLIDINVOICE_CONFIG_DIR=/etc/solidinvoice
ENV SOLIDINVOICE_DOCKER=true

EXPOSE 8765

VOLUME ["/etc/solidinvoice"]

COPY solidinvoice /usr/local/bin/solidinvoice

ENTRYPOINT ["/usr/local/bin/solidinvoice"]

CMD ["run", "--disable-https"]
