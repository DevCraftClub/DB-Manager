[![GitHub issues](https://img.shields.io/github/issues/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/issues)[![GitHub forks](https://img.shields.io/github/forks/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/network)[![GitHub license](https://img.shields.io/github/license/DevCraftClub/DB-Manager.svg?style=flat-square)](https://github.com/DevCraftClub/DB-Manager/blob/main/LICENSE)

![Текущая версия](https://img.shields.io/github/manifest-json/v/DevCraftClub/DB-Manager/main?style=for-the-badge&label=%D0%92%D0%B5%D1%80%D1%81%D0%B8%D1%8F)![Статус разработки](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.status&style=for-the-badge&label=%D0%A1%D1%82%D0%B0%D1%82%D1%83%D1%81&color=orange)

![Версия DLE](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.dle&style=for-the-badge&label=DLE)![Версия PHP](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.php&style=for-the-badge&logo=php&logoColor=777BB4&label=PHP&color=777BB4)![DevCraft Admin](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FDevCraftClub%2FDB-Manager%2Frefs%2Fheads%2Fmain%2Fmanifest.json&query=%24.devcraft&style=for-the-badge&label=DevCraft&color=blue)

# DB Manager

Плагин для экспорта и импорта базы данных DLE с учётом внешних ключей (Foreign Keys). Работает как модуль **DevCraft Admin** (версия **200.1.3** для DLE 20.0).

Сайт: https://devcraft.club/downloads/db-manager.30/

## Установка

Полная инструкция: [документация](https://readme.devcraft.club/latest/dev/db_manager/install/).

### Требования

- DLE **20.0**, PHP **8.3**
- Установленный **DevCraft Admin ≥ 200.4.0** (с поддержкой `FileResponse` для скачивания файлов)
- После установки плагина: `composer dump-autoload` в каталоге `devcraft/` на сервере

### Сборка архива

```bash
./install_archive.sh   # Linux/macOS
# или install_archive.bat на Windows
```

## Миграция с MHAdmin (вручную)

Автоматический перенос конфигурации **не выполняется**.

1. Установите [DevCraft Admin](https://readme.devcraft.club/latest/dev/devcraft_admin/install/) и DB Manager 200.1.3.
2. Скопируйте настройки модуля:

```bash
cp engine/inc/maharder/_config/db_manager.json devcraft/config/db_manager.json
```

3. При необходимости измените `export_path` на `devcraft/backup` (значение по умолчанию для новых установок).
4. Выполните в `devcraft/`:

```bash
composer dump-autoload
```

5. Удалите или отключите legacy-файлы MHAdmin для DB Manager (см. [миграция в документации](https://readme.devcraft.club/latest/dev/db_manager/migration/)).

Подробнее: [migration.md в mhdocs](https://readme.devcraft.club/latest/dev/db_manager/migration/).
