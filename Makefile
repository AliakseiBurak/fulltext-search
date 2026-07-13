.PHONY: build up down restart logs test fixtures clean

# Project name
PROJECT_NAME = manticore-search-system

# Build and start the system
build:
	docker-compose build

up:
	docker-compose up -d

down:
	docker-compose down

restart: down up

logs:
	docker-compose logs -f

logs-api:
	docker-compose logs -f php

logs-manticore:
	docker-compose logs -f manticore

logs-webui:
	docker-compose logs -f webui

# Run tests
test:
	docker-compose exec php vendor/bin/phpunit

fixtures:
	docker-compose exec php php artisan:manticore fixtures:load

# Console commands
console:
	docker-compose exec php php artisan:manticore list

# API commands
api-index-create:
	curl -X POST http://localhost:8900/api/indexes/create -H "Content-Type: application/json" -d '{"name":"test_index","version":"v1"}'

api-search:
	curl -X GET "http://localhost:8900/api/search?q=test&index_version=v1"

# Clean everything
clean:
	docker-compose down -v
	docker volume prune -f
	docker network prune -f

# Development setup
dev: build up
	@echo "System is starting..."
	@sleep 10
	@echo "Installing PHP dependencies..."
	docker-compose exec php composer install
	@echo "System ready! Access the API at http://localhost:8900"