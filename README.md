# investor-portfolio
Otus PHP Professional выпускной проект

Запуск:
docker-compose up -d --build

Перезапуск
docker-compose down -v && docker-compose up --build

Запуск тестов
vendor/bin/phpunit

Обновить подгрузку классов
composer dump-autoload

$ tree -I vendor > out.txt