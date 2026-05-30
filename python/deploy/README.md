# Микросервис для автоматического деплоя проектов

Сервис на FastAPI, который принимает вебхуки от GitHub и автоматически обновляет код на сервере.

## Состав

- `main.py` — FastAPI-приложение (эндпоинты `/webhook`, `/health`)
- `deploy.py` — логика деплоя (git clone, fetch, reset)
- `config.py` — конфигурация проектов (пути, ветки)
- `requirements.txt` — зависимости Python (FastAPI, uvicorn)
- `Dockerfile` — для сборки образа

## Как это работает

1. GitHub отправляет POST-запрос на `/webhook/{project_name}`
2. Сервер проверяет подпись (секретный токен)
3. Выполняется `git fetch` и `git reset --hard` для указанной ветки
4. Код на сервере обновляется автоматически

## Стек

- Python 3.11+
- FastAPI
- Docker
- Git
- GitHub Webhooks