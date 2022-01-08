include config.mk

SRC := plugin.yml $(shell find src resources -type f)
PHAR := TimeRanks.phar

all: phpstan $(PHAR)

phpstan:
	$(PHP_PM) $(COMPOSER_PHAR) install
	$(PHP_PM) vendor/bin/phpstan analyse

clean:
	rm -rf $(PHAR) shaded

$(PHAR): composer.lock $(SRC)
	$(PHP_PM) $(COMPOSER_PHAR) install --no-dev
	$(PHP_PM) $(SHADER_SCRIPT)
	$(PHP_PM) $(DEVTOOLS_PHAR) --make shaded --out $(PHAR)

.PHONY: phpstan clean all

