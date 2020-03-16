# Умные ссылки Bitrix [isaev.seolinks] `Bitrix 17.1+`

`Bitrix теги` `Умный фильтр` `Умный поиск` `Свои мета-теги`

Измененная концепция формирования тегирования.  
В данном модуле используется ORM сущность и пользовательское поле для разделов. Существуют события для работы с тегированием, что позволяет добавить свой функционал не затрагивая сам модуль.  
 
---
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
** Это стандартный `<select>` блок в котором мы выбираем ссылки, которые должны быть показаны в разделе**

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
    "FIELDS" => ['ID', 'FROM', 'TAG_NAME', 'GROUP_NAME', 'TO, 'SORT'], // Можем указать дополнительные поля, которые хотим вывести 
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
* `TO` - URL с контентом (Подмена). Поле описано в [Работа с ссылками](#работа-с-ссылками);
* `REDIRECT` - Редирект с `TO` на `FROM`;
* `H1` - Заголовок H1 ;
* `META_TITLE` - `<title>` - мета тег;
* `META_DESCRIPTION` - `<meta name="description">` - мета тег descriptiom;
* `TEXT` - Текст можно использовать по своему усмотрению;
* `TAG_NAME` - Название тега для компонента;
* `GROUP_NAME"` - Название группы. Можно использовать в компоненте для формирования групп (разделов) тегирования;

## Работа с ORM:  
[Как работать с ORM Bitrix](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=05748)  
**Сущность `\Isaev\Seolinks\SeolinksTable`**  

### Примеры:  
* Вывод всех ссылок: 
    ```PHP
    $arResult = SeolinksTable::getList(['filter' => [], 'select' => ['*']])->fetchAll();
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