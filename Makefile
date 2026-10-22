PLUGIN := wp-course-listings
ZIP    := $(PLUGIN).zip

.PHONY: build dist lint fix pot check-version clean

build: dist

# Package the committed plugin files; development files are left out via
# export-ignore in .gitattributes.
dist:
	git archive --format=zip --prefix=$(PLUGIN)/ -o $(ZIP) HEAD
	@echo "Build complete: $(ZIP)"

lint: vendor
	vendor/bin/phpcs

fix: vendor
	vendor/bin/phpcbf

# Requires WP-CLI.
pot:
	wp i18n make-pot . languages/$(PLUGIN).pot --exclude=vendor,bin \
		--headers='{"Report-Msgid-Bugs-To":"https://github.com/kamodev/wp-course-listings/issues"}'

check-version:
	bin/check-version.sh

vendor: composer.json composer.lock
	composer install
	@touch vendor

clean:
	rm -f $(ZIP)
