FROM python:3.9-alpine

WORKDIR /opt/docs

RUN apk update && apk add make nodejs npm
RUN pip install sphinx sphinx_rtd_theme sphinx-autobuild alabaster sphinx_bootstrap_theme watchdog && \
    npm install --global gulp && \
    python -m pip install --upgrade pip

EXPOSE 8000

CMD watchmedo shell-command --ignore-pattern='_build/*' --recursive --command='make html'
