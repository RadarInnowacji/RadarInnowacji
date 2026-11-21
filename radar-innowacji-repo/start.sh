#!/usr/bin/env sh
# Radar Innowacji – uruchomienie lokalne (macOS / Linux)
cd "$(dirname "$0")" || exit 1
PORT="${1:-8000}"
command -v php >/dev/null 2>&1 || { echo "Nie znaleziono PHP. Zainstaluj PHP 8.1+ (instrukcja w README.md)."; exit 1; }
php setup.php --port="$PORT" || exit 1
echo "Serwer działa: http://localhost:$PORT   (zatrzymanie: Ctrl+C)"
( sleep 1; (command -v xdg-open >/dev/null && xdg-open "http://localhost:$PORT") || (command -v open >/dev/null && open "http://localhost:$PORT") ) >/dev/null 2>&1 &
exec php -S "localhost:$PORT" dev-router.php
