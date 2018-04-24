ifndef APP_ENV
	include .env
endif

.PHONY: list cache-clear cache-warmup assets dev-server test phpunit mocha fixtures

list:
	@$(MAKE) -pRrq -f $(lastword $(MAKEFILE_LIST)) : 2>/dev/null | awk -v RS= -F: '/^# File/,/^# Finished Make data base/ {if ($$1 !~ "^[#.]") {print $$1}}' | sort | egrep -v -e '^[^[:alnum:]]' -e '^$@$$' | xargs

cache-clear:
	@bin/console cache:clear --no-warmup

cache-warmup: cache-clear
	@bin/console cache:warmup

assets:
	bin/console maba:webpack:compile

dev-server:
	bin/console maba:webpack:dev-server

test: phpunit mocha

phpunit:
	./bin/phpunit

mocha:
	yarn run test
