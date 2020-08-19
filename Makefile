protos = $(wildcard protos/*.proto)


test: protoc
	vendor/bin/phpunit tests


protoc: $(protos)
	@find out-$@ ! -path out-$@ ! -name '.gitignore' -exec rm -rf {} +
	@protoc --proto_path=protos --$@_out=out-$@ $^
	@echo generated $@


release: test
ifeq ($(strip $(version)),)
	@echo "\033[31mERROR:\033[0;39m No version provided."
	@echo "\033[1;30mmake release version=1.0.0\033[0;39m"
else
	@git rev-parse "v$(version)" >/dev/null 2>&1 && { echo "git tag v$(version) already exists"; exit 1; } || :;
	git tag v$(version)
	git push origin master
	git push origin --tags
	@echo "\033[32mv${version} released\033[0;39m"
endif

