
COMPOSE = docker compose
BACKEND = erp_php
FRONTEND = erp_frontend
MYSQL = erp_mysql
NGINX = erp_nginx

.DEFAULT_GOAL := help

# ─── 1. Полный старт (фронт + бэк) ──────────────────────────────
.PHONY: up
up: ## Поднять всё: Docker (nginx, php, mysql) + frontend (npm dev)
	$(COMPOSE) up -d --build
	@echo ""
	@echo "Всё запущено"
	@echo "    Backend API  → http://localhost:8080"
	@echo "    MySQL        → localhost:3306"

.PHONY: up-back
up-back: ## Поднять только бэкенд (nginx + php + mysql)
	$(COMPOSE) up -d --build nginx php mysql



# ─── 3. Рестарт бэкенда ──────────────────────────────────────────
.PHONY: restart-back
restart-back: ## Перезапустить бэкенд (PHP-FPM + Nginx)
	$(COMPOSE) restart php nginx
	@echo "🔄  Backend перезапущен"

.PHONY: restart
restart: ## Перезапустить всё
	$(COMPOSE) restart
	@echo "🔄  Все сервисы перезапущены"

# ─── 4. Остановка ────────────────────────────────────────────────
.PHONY: down
down: ## Остановить и удалить все контейнеры
	$(COMPOSE) down
	@echo "⬇️   Все контейнеры остановлены"


.PHONY: down-back
down-back: ## Остановить только бэкенд
	$(COMPOSE) stop nginx php
	@echo "  Backend остановлен"

.PHONY: nuke
nuke: ## Остановить всё + удалить volumes 
	$(COMPOSE) down -v
	@echo "  Контейнеры и volumes удалены"

# ─── 5. Логи ─────────────────────────────────────────────────────
.PHONY: logs
logs: ## Логи всех сервисов 
	$(COMPOSE) logs -f --tail=100

.PHONY: logs-back
logs-back: ## Логи бэкенда (php + nginx)
	$(COMPOSE) logs -f --tail=100 php nginx


.PHONY: logs-db
logs-db: ## Логи MySQL
	$(COMPOSE) logs -f --tail=100 mysql

.PHONY: shell-php
shell-php: ## Зайти в контейнер PHP
	docker exec -it $(BACKEND) sh

.PHONY: shell-db
shell-db: ## Открыть MySQL CLI
	docker exec -it $(MYSQL) mysql -u erp_user -psecret erp_training

.PHONY: artisan
artisan: ## Выполнить artisan-команду: make artisan CMD="migrate"
	docker exec -it $(BACKEND) php artisan $(CMD)

.PHONY: composer
composer: ## Выполнить composer-команду: make composer CMD="require laravel/sanctum"
	docker exec -it $(BACKEND) composer $(CMD)


.PHONY: migrate
migrate: ## Запустить миграции Laravel
	docker exec -it $(BACKEND) php artisan migrate

.PHONY: seed
seed: ## Запустить сиды Laravel
	docker exec -it $(BACKEND) php artisan db:seed

.PHONY: fresh
fresh: ## Пересоздать БД с сидами
	docker exec -it $(BACKEND) php artisan migrate:fresh --seed

.PHONY: ps
ps: ## Статус контейнеров
	$(COMPOSE) ps

# ─── Help ─────────────────────────────────────────────────────────
.PHONY: help
help: ## Показать список команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
	@echo ""
