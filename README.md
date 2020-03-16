# Умные ссылки Bitrix [isaev.seolinks] `Bitrix 17.1+`

`Bitrix теги` `Умный фильтр` `Умный поиск` `Свои мета-теги`

Измененная концепция формирования тегирования.  
В данном модуле используется ORM сущность и пользовательское поле для разделов. Существуют события для работы с тегированием, что позволяет добавить свой функционал не затрагивая сам модуль.  

---
## Содержание:  
* [#Возможности]()
* [#Установка](#%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)
* [#Работа с ссылками](#%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B0-%D1%81-%D1%81%D1%81%D1%8B%D0%BB%D0%BA%D0%B0%D0%BC%D0%B8)
* [#События](#%D1%81%D0%BE%D0%B1%D1%8B%D1%82%D0%B8%D1%8F)
* [#Привязка ссылок в разделах](#%D0%BF%D1%80%D0%B8%D0%B2%D1%8F%D0%B7%D0%BA%D0%B0-%D1%81%D1%81%D1%8B%D0%BB%D0%BE%D0%BA-%D0%B2-%D1%80%D0%B0%D0%B7%D0%B4%D0%B5%D0%BB%D0%B0%D1%85)
* [#Вывод компонента в шаблоне](#%D0%B2%D1%8B%D0%B2%D0%BE%D0%B4-%D0%BA%D0%BE%D0%BC%D0%BF%D0%BE%D0%BD%D0%B5%D0%BD%D1%82%D0%B0-%D0%B2-%D1%88%D0%B0%D0%B1%D0%BB%D0%BE%D0%BD%D0%B5)
* [#Доступные поля сущности](#%D0%B4%D0%BE%D1%81%D1%82%D1%83%D0%BF%D0%BD%D1%8B%D0%B5-%D0%BF%D0%BE%D0%BB%D1%8F-%D1%81%D1%83%D1%89%D0%BD%D0%BE%D1%81%D1%82%D0%B8)
* [#Работа с ORM](#%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B0-%D1%81-orm)
    * [#Примеры](#%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B)
---
## Возможности:
Или "Что я буду с этим делать?"  
Данный модуль это каркас для работы с ссылками и содержанием поверх инфоблоков.  
**Примеры:**  
* На сайте нестандартные умные фильтры или сортирвка. Сеошники хотят, чтобы было все красиво и у определнных фильтров и сортировок были свои теги и ЧПУ.
* На сайте выгрузка настроена так, что изменять мета-теги, название или текст можно только через выгрузку.  
* Нужно сгенерирвоать тегирование и мета-теги на умный фильтр.
* Нужно сформировать тегирования и мета-теги для поисковых запросовю.   
 
Исходя из задачи мы можем создать отдельный компонент, настроить массовую выгрузку или сделать шаблонное формирование. Данный модель дает заготовку.  
Можно через ORM создать формирование тегирования фильтра исходя из правил. Которые будут записываться в таблицу.  

## Установка:  
* Перемещаем папку модуля в `/bitrix/modules` 
* Переходим в админке в "Установленные решения" `/bitrix/admin/partner_modules.php?lang=ru`.
* Устанавливаем "Isaev: Seo-ссылки (Независимые мета-теги) (isaev.seolinks)".  
## Работа с ссылками:  
После установки, в админке, в разделе "Сервисы" появится вкладка "SEO-ссылки - Cписок" в нем стандартная таблица для работы с элементами
* У ссылок есть поле "URL с контентом (Подмена):" - это эксперементальная подмена любой ссылки в пределах инфоблока.
Т.е если нам нужно сделать "Умную сортирвку" по адресу `/catalog/phone?price=DESC` по адресу `/catalog/phone/min-price/`:
  * "URL" - `/catalog/phone/min-price/`
  * "URL с контентом (Подмена):" - `/catalog/phone?price=DESC`  
  
:warning:  Важно! Вы не сможете сформировать из `/catalog/phone?price=DESC` ссылку `/custom_section/min_price_phone` все должно работать по правилам ЧПУ инфоблока  

![Список ссылок](https://gist.githubusercontent.com/Isa3v/0dca1f2ef54f26add307006e2a4ae028/raw/567be7d678401a0a297d63ffeaa1199fb70f83b2/screely-1584359533223.png)  

![Добавление ссылки](https://gist.githubusercontent.com/Isa3v/0dca1f2ef54f26add307006e2a4ae028/raw/567be7d678401a0a297d63ffeaa1199fb70f83b2/screely-1584359529004.png)  

## События:  
`Вызываются как и все события в init.php`  

**Событие перед подменой контента ссылки:**  
Событие перед подменой контента (Инициализацией Context)  
Можно внести свои правила и добавить нужные параметры в `$arServer` переменную.  
Вызывается в методе `setSpoof` класса Handler  
```PHP
\Bitrix\Main\EventManager::getInstance()->addEventHandler('isaev.seolinks','beforeFindSpoof','beforeSpoofingFunction');
function beforeSpoofingFunction($event){
  $arResult = $event->getParameters();
  return $arResult;
}
```

**Событие после подмены контента ссылки:**  
В данном событие возвращать ничего не получится. Но можно обработать уже сформированные данные. Например заменить $_GET или проверить `$arServer`.  
Вызывается в методе `setSpoof` класса Handler
```PHP
\Bitrix\Main\EventManager::getInstance()->addEventHandler('isaev.seolinks','afterSpoofing','afterSpoofingFunction');
function afterSpoofingFunction($event){
  $event->getParameters();
}
```
** Событие перед формированием мета-тегов модуля **  
В данное событие передается массив `$arMeta` содержащий уже подготовленные мета-теги. Это последний шанс все изменить :)  
Для изменения возвращаем в событии изменный массив  
```PHP
\Bitrix\Main\EventManager::getInstance()->addEventHandler('isaev.seolinks','beforeMeta','beforeMetaFunction');
function beforeMetaFunction($event){
  $arMeta = $event->getParameters();
  $arResult['description'] = 'test';
  return $arResult;
}
```  
## Привязка ссылок в разделах:  
Для привязки к разделам используется пользовательское свойство "SEO-ссылки привязка к разделу" (После установки становится доступным)  
**Это стандартный `<select>` блок в котором мы выбираем ссылки, которые должны быть показаны в разделе**  
![Польз. поле](https://gist.githubusercontent.com/Isa3v/0dca1f2ef54f26add307006e2a4ae028/raw/3ef47f7f6828f96fc3af5206d484c2983d61853f/screely-1584361294722.png)  

## Вывод компонента в шаблоне: 
После установки, будет доступен новый компонент `isaev:seolinks.list`. Добавляем  
Чтобы получить привязанные ссылки у раздела получаем поль. поле оно будет ввиде строки c ID, разделенной ";"  

```PHP
// Делаем из строки массив 
$arLinks = explode(";", $section['UF_SEO_LINKS']);
// Вызываем компонент
$APPLICATION->IncludeComponent("isaev:seolinks.list", "", 
  Array(
    "ID" => $arLinks, // Массив ID ссылок
    "FIELDS" => ['ID', 'FROM', 'TAG_NAME', 'GROUP_NAME', 'TO', 'SORT'], // Можем указать дополнительные поля, которые хотим вывести 
    "SORT_FIELD1" => "SORT", // Сортируем по полю
    "SORT_DIRECTION1" => "ASC"
    "SORT_FIELD2" => "SORT",
    "SORT_DIRECTION2" => "ASC"
    ),false
  );
```

## Доступные поля сущности: 
* `ID` - Идентификатор ссылки. Указывается автоматически;
* `ACTIVE` - Активность ссылки;
* `SORT` - Сортировка;
* `FROM` - URL на котором будут заменены мета-теги;
* `TO` - URL с контентом (Подмена). Поле описано в [работа с ссылками](#работа-с-ссылками);
* `REDIRECT` - Редирект с `TO` на `FROM`;
* `H1` - Заголовок H1 ;
* `META_TITLE` - `<title>` - мета тег;
* `META_DESCRIPTION` - `<meta name="description">` - мета тег description;
* `TEXT` - Текст можно использовать по своему усмотрению;
* `TAG_NAME` - Название тега для компонента;
* `GROUP_NAME"` - Название группы. Можно использовать в компоненте для формирования групп (разделов) тегирования;

## Работа с ORM:  
[Как работать с ORM Bitrix](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=05748)  
**Сущность `\Isaev\Seolinks\SeolinksTable`**  

### Примеры:  
* Вывод всех ссылок: 
    ```PHP
    $arResult = \Isaev\SeoLinks\SeolinksTable::getList(['filter' => [], 'select' => ['*']])->fetchAll();
    ```
* Получить значение полей сущности:
    ```PHP
    $arColumns = \Isaev\SeoLinks\SeolinksTable::getEntity()->getFields();
    ```  

:warning:   В некоторых случаях нужно сначала подключить модуль. 
```PHP
\Bitrix\Main\Loader::includeModule('isaev.seolinks');
```
Остальные примеры работы с ORM, есть в курсе, ссылка на который выше.