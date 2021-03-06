BUILD_DIR      = infrastructure/build
REPOSITORY_DIR = $(BUILD_DIR)/repository
REPOSITORY_URL = https://github.com/lzakrzewski/simple-prepaid-card.git
PACKAGE_DIR    = $(BUILD_DIR)/package

build_package:
	rm -rf $(REPOSITORY_DIR)
	rm -rf $(PACKAGE_DIR)
	mkdir -p $(REPOSITORY_DIR)
	mkdir -p $(PACKAGE_DIR)
	git clone --depth 1 $(REPOSITORY_URL) $(REPOSITORY_DIR)
	tar --directory $(REPOSITORY_DIR) -czf $(PACKAGE_DIR)/application.tar.gz .
	rm -rf $(REPOSITORY_DIR)

deploy: build_package
	ansible-playbook -i infrastructure/inventories/default/inventory infrastructure/deploy.yml --ssh-common-args="-o StrictHostKeyChecking=no"

redis_up:
	docker run --name redis -h redis -p 6379:6379 -d redis

redis_down:
	-docker stop redis
	-docker rm redis

test_ci:
	composer test-ci
