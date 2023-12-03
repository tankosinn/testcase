# Test Case

Yurtlar için basit bir rezarvasyon/kayıt yönetim sistemidir.

# Uygulama İçi Ekran Görüntüleri
[Giriş Yap](https://i.imgur.com/YIuq4Nf.png)

[Yurt İşletme Yönetimi](https://i.imgur.com/Vv8lorJ.png)

[Oda Yönetimi](https://i.imgur.com/HSwpHSo.png)

## Bağımlılıklar
- PHP 7.4.x
- Node.js
- Symfony 5.4

## Yükleme ve Dağıtım
Paket yönetim programlarını kullanınız: composer, yarn/npm.

```bash
git clone https://tankosinn/testcase.git

cd testcase

composer install

yarn install
```

## Veritabanı

```bash
symfony console doctrine:database:create

symfony doctrine:schema:update --force
```

## Console Commands
Console command ile birlikte, üç farklı kullanıcı türü içinde kayıt oluşturabilirsiniz.

Her bir kullanıcı için girilmesi gereken alanlar değişebilir.

```bash
symfony console app:create-user

```

## Kullanım
```bash
yarn watch

symfony server:start
```

## To-do
- [x] Admin Management
- [ ]  Students checkIn and departureDate
    - [x]  Store Fields
    - [ ]  Email notification
- [ ]  Inventory Management
    - [ ]  List
        - [ ]  Ware/In Room/In Usage
    - [x]  Store
- [ ]  Room Management
    - [x]  List
    - [x]  Store
    - [ ]  Detail
        - [x]  Students
        - [ ]  Inventory
            - [ ]  List
            - [ ]  Store
- [ ]  Student Panel
    - [ ]  Faults
    - [ ]  Leave Request
- [ ]  Faults
- [ ]  Leave Requests

## License

[MIT](https://choosealicense.com/licenses/mit/)
