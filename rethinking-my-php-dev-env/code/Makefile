init:
	docker volume create composer-cache; \
	docker volume create redis-data

build: 
	cd docker-compose; \
	docker compose create --build; \
	docker image prune --force
	

