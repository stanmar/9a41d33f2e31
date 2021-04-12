Для реализации требований о сложности методов вставки и получения элементов, за основу взял хэш таблицу с разрешением коллизий по методу цепочек

TODO:
    Добавить блокировку таблицы для PUT , чтобы избежать race conditions
    Добавить тест, в котором одновременно с памятью будут работать несколько процессов

1) нужен docker-composer 1.28 +
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version

2) Запускать для инициализации. В переменной окружения в сервисе настраивается размер выделяемой памяти
docker-compose up
или
docker-compose run --rm -T init

3) Команды для демонстрации работы

- заполняет N (переменная окружения) рандомными записями:
docker-compose run --rm -T put_n_randoms

- получение N (переменная окружения) первых записей по ключам от 1 до N:
docker-compose run --rm -T get_n_first

- Внести значение с ключом 123 и значением 123
docker-compose run --rm -T put 22 123

- Получить значение с ключом 123
docker-compose run --rm -T get 123

- Удалить блок
docker-compose run --rm -T php delete.php