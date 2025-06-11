<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// пространство имен для работы с языковыми файлами
use Bitrix\Main\Localization\Loc;
// пространство имен для всех исключений в системе
use Bitrix\Main\SystemException;
// пространство имен для загрузки необходимых файлов, классов, модулей
use Bitrix\Main\Loader;
// пространство имен для кеша
use Bitrix\Main\Application;

// основной класс, является оболочкой компонента унаследованного от CBitrixComponent
class Popup extends CBitrixComponent
{

    // выполняет основной код компонента, аналог конструктора (метод подключается автоматически)
    public function executeComponent()
    {
        try {
            // подключаем метод проверки подключения модуля
            $this->checkModules();
            // подключаем метод подготовки массива $arResult
            $this->getResult();
        } catch (SystemException $e) {
            ShowError($e->getMessage());
        }
    }

    // подключение языковых файлов (метод подключается автоматически)
    public function onIncludeComponentLang()
    {
        Loc::loadMessages(__FILE__);
    }

    // проверяем установку модуля (метод подключается внутри класса try...catch)
    protected function checkModules()
    {
        // если модуль не подключен
        if (!Loader::includeModule("dd.tools")) {
            // выводим сообщение в catch
            throw new SystemException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
        }
    }

    // обработка массива $arParams (метод подключается автоматически)
    public function onPrepareComponentParams($arParams)
    {
        // время кеширования
        if (!isset($arParams['CACHE_TIME'])) {
            $arParams['CACHE_TIME'] = 3600;
        } else {
            $arParams['CACHE_TIME'] = intval($arParams['CACHE_TIME']);
        }
        // возвращаем в метод новый массив $arParams     
        return $arParams;
    }

    // подготовка массива $arResult (метод подключается внутри класса try...catch)
    protected function getResult()
    {
        // если нет валидного кеша, получаем данные из БД
        if ($this->startResultCache()) {
            // путь для кеша
            $cachePath = "/" . SITE_ID . $this->GetRelativePath();
            // служба пометки кеша тегами
            $taggedCache = Application::getInstance()->getTaggedCache();
            // начинаем кеширование для заданной папки
            $taggedCache->startTagCache($cachePath);
            // помечаем кеш своим тегом
            $taggedCache->registerTag('popup');
            // запрос к базе через класс ORM
            $query = new Bitrix\Main\Entity\Query(
                \DD\Tools\EntityDataTable::getEntity()
            );
            $this->arResult = $query->setSelect(['*'])
                ->setFilter(['=ID' => 1])
                ->setCacheTtl(3600)
                ->fetch();

            // формируем arResult["SITE"]
            $this->arResult["SITE"] = json_decode($this->arResult['SITE']);

            // записываем в глобальную переменную
            if (!empty($this->arResult["EXCEPTIONS"])) {
                $this->arResult["EXCEPTIONS"] = preg_split("/\r\n|\n|\r/", $this->arResult['EXCEPTIONS']);
            }

            // записываем кеш
            $taggedCache->endTagCache();

            // получаем настройки модуля 
            $this->arResult["SETTINGS"] = \Bitrix\Main\Config\Option::getForModule("dd.tools");

            // сохраняем полученные данные в кеш
            $this->EndResultCache();
        }

        // подключаем шаблон и без записи в кеш
        $this->IncludeComponentTemplate();
    }
}
