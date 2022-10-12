# BotEngineVK
## Бот для ВК с открытым исходным кодом и модульной системой<br><br>

Описания настроек находятся в файле настроек.<br>

<br><br>
### Что нужно знать перед использованием
1. В описаниях команд **<>** означают обязательный параметр, **[]** - необязательный;
2. Параметры и цели разбиты на 2 переменные, поэтому неважно какой будет порядок. Например: `!bot peerid @deathscore13` и `!bot @deathscore13 peerid`;
3. Цели берутся сначала из текста сообщения, а после идут отвеченное/пересланные сообщения.

<br><br>
### Команды
1. **bot** <подкоманда> - Основные команды бота
* **analysis** [команда] - Анализ скорости
* **peerid** [цель] - peer_id чата
* **info** - Информация о боте
* **modules** [номер] - Список модулей или информация об указанном модуле
* **commands** - Список команд

<br><br>
### Требования
1. PHP 8.0.0+;
2. MySQL.

<br><br>
### Установка и настройка
1. Загружаем файлы на веб сервер (можно в отдельную папку, например **`botvk`**);
2. Создаём сообщество, если его ещё нет;
3. Переходим в **Управление --> Сообщения**, выставляем **Сообщения сообщества** на **Включено** и сохраняем;
4. Переходим в **Настройки для бота**, выставляем **Возможности ботов** на **Включено** и сохраняем;
5. Если нужно добавить бота в чат, то ставим галочку на **Разрешать добавлять сообщество в чаты**, сохраняем,
переходим на страницу сообщества и видим что в **Меню** появилось **Добавить в чат**. Добавляем в нужный чат, убираем галочку с
**Разрешать добавлять сообщество в чаты** и сохраняем;
6. Переходим в **Настройки --> Работа с API --> CallBack API**, создаём сервер с версией API 5.131, в поле **Адрес** указываем ссылку
на директорию с ботом + **`botenginevk`**, например: **`https://example.com/botvk/botenginevk`** (файлы бота лежат в **`botvk`**);
7. Открываем **`configs/engine.php`**, указываем строку которую должен вернуть сервер, в настройках **CallBack API** сервера придумываем и указываем
секретный ключ, сохраняем его и копируем в настройки бота. Далее указываем **group_id** сообщества, сохраняем **`configs/engine.php`** и жмём
**Подтвердить** в настройках **CallBack API** сервера;
8. Настраиваем в **`configs/engine.php`** подключение к MySQL, меняем другие настройки по необходимости и сохраняем;
9. Переходим в **Типы событий** и ставим галочку на **Входящее сообщение** и **Исходящее сообщение**;
10. Готово.
