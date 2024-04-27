<?php

$access = require 'access.php';

use sbisAPI\SbisCRM;
require_once 'SbisCRM.php';

try {

    $sbis = new SbisCRM($access['login'], $access['password']);

    try {
        $phoneNumber = '89112222984';
        $clientID = $sbis->getCustomerByPhone($phoneNumber);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo "<br><br>Клиент с телефоном $phoneNumber ";
    if (isset($clientID))
        echo "найден, ID=$clientID";
    else
        echo 'не найден';

    try {
        $surname     = 'Test_1';
        $name        = 'Test_2';
        $patronymic  = 'Test_3';
        $address     = 'Address';
        $birthday    = '2000-01-01';
        $phone       = '80001112233';
        $email       = 'test222@test.test';
        $newClientID = $sbis->saveCustomer($surname, $name, $patronymic, $address, $birthday, $phone, $email);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    if (isset($newClientID))
        echo "<br><br>Клиент создан, ID=$newClientID";
    else
        echo '<br><br>Ошибка создания клиента';

    try {
        $description  = '<br><br>Пример сделки ' . password_hash(date('Ymd'), PASSWORD_DEFAULT);
        $leadID = $sbis->saveLead($name, $phone, $email, $description);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    if (isset($leadID))
        echo "<br><br>Сделка создана, ID=$leadID";
    else
        echo '<br><br>Ошибка создания сделки';

} catch (Exception $e) {
    echo $e->getMessage();
}


use sbisAPI\SbisStore;
require_once 'SbisStore.php';

try {

    $sbis = new SbisStore($access['app_client_id'], $access['app_secret'], $access['secret_key']);

    try {
        echo '<br><br>Список точек продаж';
        $url = 'https://api.sbis.ru/retail/point/list?product=retail&withPhones=true&withPrices=true&withSchedule=true&page=0&pageSize=10';
        var_dump($sbis->getDataByUrl($url));
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    try {
        $poinId = 231; // определяется по списку точек продаж
        echo '<br><br>Список прайсов точки продаж с ID=' . $poinId;
        $url = 'https://api.sbis.ru/retail/nomenclature/price-list?pointId=' . $poinId . '&actualDate=' . date('Y-m-d');
        var_dump($sbis->getDataByUrl($url));
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    try {
        $priceId = 6; // определяется по списку прайсов
        echo '<br><br>Прайс-лист с ID_PRICE=' . $priceId;
        $url = 'https://api.sbis.ru/retail/nomenclature/list?product=delivery&pointId=' . $poinId . '&priceListId=' . $priceId . '&withBalance=true&withBarcode=true&onlypublished=true&page=0&pageSize=1000';
        var_dump($sbis->getDataByUrl($url));
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    echo '<br><br>';

} catch (Exception $e) {
    echo $e->getMessage();
}

