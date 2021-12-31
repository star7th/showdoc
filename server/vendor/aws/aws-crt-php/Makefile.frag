
INT_DIR=build/install
GENERATE_STUBS=$(shell expr `php --version | head -1 | cut -f 2 -d' '` \>= 7.1)

CMAKE = cmake3
ifeq (, $(shell which cmake3))
	CMAKE = cmake
endif

# default to using system OpenSSL, if disabled aws-lc will be used
USE_OPENSSL ?= ON
ifneq (OFF,$(USE_OPENSSL))
	CMAKE_USE_OPENSSL=-DUSE_OPENSSL=ON
	# if a path was provided, add it to CMAKE_PREFIX_PATH
	ifneq (ON,$(USE_OPENSSL))
    	CMAKE_PREFIX_PATH=-DCMAKE_PREFIX_PATH=$(USE_OPENSSL)
	endif
endif

CMAKE_CONFIGURE = $(CMAKE) \
    -DCMAKE_INSTALL_PREFIX=$(INT_DIR) \
    -DBUILD_TESTING=OFF \
    -DCMAKE_BUILD_TYPE=$(CMAKE_BUILD_TYPE) \
    $(CMAKE_USE_OPENSSL) \
    $(CMAKE_PREFIX_PATH)
CMAKE_BUILD = CMAKE_BUILD_PARALLEL_LEVEL='' $(CMAKE) --build
CMAKE_BUILD_TYPE ?= RelWithDebInfo
CMAKE_TARGET = --config $(CMAKE_BUILD_TYPE) --target install

all: extension 
.PHONY: all extension 

# configure for static aws-crt-ffi.a
build/aws-crt-ffi-static/CMakeCache.txt:
	$(CMAKE_CONFIGURE) -Hcrt/aws-crt-ffi -Bbuild/aws-crt-ffi-static -DBUILD_SHARED_LIBS=OFF

# build static libaws-crt-ffi.a
build/aws-crt-ffi-static/libaws-crt-ffi.a: build/aws-crt-ffi-static/CMakeCache.txt
	$(CMAKE_BUILD) build/aws-crt-ffi-static $(CMAKE_TARGET)

# PHP extension target
extension: ext/awscrt.lo

# Force the crt object target to depend on the CRT static library
ext/awscrt.lo: ext/awscrt.c

ext/awscrt.c: build/aws-crt-ffi-static/libaws-crt-ffi.a ext/api.h ext/awscrt_arginfo.h

ext/awscrt_arginfo.h: ext/awscrt.stub.php gen_stub.php
ifeq ($(GENERATE_STUBS),1)
	# generate awscrt_arginfo.h
	php gen_stub.php --minimal-arginfo ext/awscrt.stub.php
endif

# transform/install api.h from FFI lib
src/api.h: crt/aws-crt-ffi/src/api.h
	php gen_api.php crt/aws-crt-ffi/src/api.h > src/api.h

# install api.h to ext/ as well
ext/api.h : src/api.h
	cp -v src/api.h ext/api.h

ext/php_aws_crt.h: ext/awscrt_arginfo.h ext/api.h

vendor/bin/phpunit:
	composer update

test-extension: vendor/bin/phpunit extension
	composer run test-extension

# Use PHPUnit to run tests
test: test-extension
