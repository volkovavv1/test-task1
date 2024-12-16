<?php
namespace App;
require_once 'Infrastructure/sdbh.php'; use sdbh\sdbh; 

use DateTime;

class Calculate
{
    public function calculate1()
    {
        $dbh = new sdbh();

        //Получаем из формы даты начала и окончания аренды
        $first_day = isset($_POST['first_day']) ? $_POST['first_day'] : 0;
        $last_day = isset($_POST['last_day']) ? $_POST['last_day'] : 0;

        //Если даты начала и окончания аренды не введены или некорректно введены, то выводим позже ошибку
        //Если все в порядке то выполняем функцию вычисления разницы между 2 днями
        $days = (empty($first_day) or empty($last_day)) ? -1 : calculateDays($first_day, $last_day);

        $product_id = isset($_POST['product']) ? $_POST['product'] : 0;
        $selected_services = isset($_POST['services']) ? $_POST['services'] : [];
        $product = $dbh->make_query("SELECT * FROM a25_products WHERE ID = $product_id");
        if ($product) {
            $product = $product[0];
            $price = $product['PRICE'];
            $tarif = $product['TARIFF'];
        } else {
            echo "Ошибка, товар не найден!";
            return;
        }

        $tarifs = unserialize($tarif);
        if (is_array($tarifs)) {
            $product_price = $price;
            foreach ($tarifs as $day_count => $tarif_price) {
                if ($days >= $day_count) {
                    $product_price = $tarif_price;
                }
            }
            $total_price = $product_price * $days;
        }else{
            $total_price = $price * $days;
        }

        $services_price = 0;
        foreach ($selected_services as $service) {
            $services_price += (float)$service * $days;
        }

        $total_price += $services_price;
        //Если даты начала и окончания аренды не введены или дата окончания аренды наступает раньше, чем дата начала, то расчет стоимости будет неверным и выводит ошибку
        if ($total_price < 0) {
            echo "Ошибка ввода!";
            return;
        } else {
            echo $total_price;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instance = new Calculate();
    $instance->calculate1();
}

function calculateDays($first_day, $last_day) {
    //Перевод данных, полученных из формы, из формата строки в формат даты
    $day1 = DateTime::createFromFormat('d.m.Y', $first_day);
    $day2 = DateTime::createFromFormat('d.m.Y', $last_day);

    //Если дата окончания аренды раньше, чем дата начала, то возвращаем -1 
    if ($day1 > $day2) {
        return -1;
    } else {
    //Вычисляем разницу между двух дат и прибавляем 1 день, чтобы учесть день начала аренды      
        $interval = $day1->diff($day2);
        return ($interval->days + 1); 
    }
}