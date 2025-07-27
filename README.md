# investor-portfolio
Otus PHP Professional выпускной проект
Проект - портфель инвестора.
На данный момент реализованно:
- Добавление новых пользователей по GET `http://195.133.194.43/register`
- Импорт из OKX в БД списка активов пользователя POST `http://195.133.194.43/balances/import`
- Просмотр текущего списока активов и подсчет актуальной стоимости в USDT по GET `http://195.133.194.43/balances`

Запуск проекта локально:
docker-compose up -d --build

Запуск тестов:
vendor/bin/phpunit


