[![GitHub issues](https://img.shields.io/github/issues/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/issues)[![GitHub forks](https://img.shields.io/github/forks/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/network)[![GitHub license](https://img.shields.io/github/license/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/blob/main/LICENSE)
# DLE Faker

![Текущая версия](https://img.shields.io/github/manifest-json/v/DevCraftClub/DB-Manager/main?style=for-the-badge&label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F)![Статус разработки](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.status&style=for-the-badge&label=%D0%A1%D1%82%D0%B0%D1%82%D1%83%D1%81&color=orange)

![Версия DLE](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.dle&style=for-the-badge&label=DLE)![Версия PHP](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.php&style=for-the-badge&logo=php&logoColor=777BB4&label=PHP&color=777BB4)![Версия MHAdmin](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.mhadmin&style=for-the-badge&label=MH-ADMIN&color=red)

# DB Manager

Данный проект предназначался изначально для того, чтобы без проблем экспортировать данные с внешними ключами (Foreign Keys). Стандартный метод экспорта (от Sypex Dumper) к сожалению игнорирует данные ключи. Из-за чего возникает проблема с восстановлением данных. А поскольку я часто использую эти ключи, то частенько получаю сообщения об этом. Посему было решено написать простой плагин, который позволяет быстро экспортировать данные с внешними ключами.

Обратная совместимость со стандартным методом восстановления работает без проблем. В качестве бонуса я добавил экспорт данных в канал телеграма.

## Установка

Процесс установки описан в [документации](https://readme.devcraft.club/latest/dev/db_manager/install/).