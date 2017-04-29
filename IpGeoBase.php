<?php

namespace div\geoip;


use yii\base\Component;

class IpGeoBase extends Component
{
    /**
     * используемая кодировка
     * @var string
     */
    public $charset = 'utf-8';

    public $ip;


    /**
     * Создаем объект с заданными настройками.
     * @return void
     */
    public function init()
    {
        if (!$this->ip) {
            $this->ip = $this->getIp();
        }
    }

    /**
     * функция возвращет конкретное значение из полученного массива данных по ip
     * @param string - ключ массива. Если интересует конкретное значение.
     * Ключ может быть равным 'inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng'
     * @param boolean - устанавливаем хранить данные в куки или нет
     * Если true, то в куки будут записаны данные по ip и повторные запросы на ipgeobase происходить не будут.
     * Если false, то данные постоянно будут запрашиваться с ipgeobase
     * @return string|null - дополнительно читайте комментарии внутри функции.
     */
    public function getValue($key)
    {
        $key_array = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        if (!in_array($key, $key_array)) {
            $key = null;
        }
        $data = $this->getData();
        if ($key) { // если указан ключ
            if (isset($data[$key])) { // и значение есть в массиве данных
                return $data[$key]; // возвращаем строку с нужными данными
            } else { // иначе если были включены куки
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Получаем данные с сервера или из cookie
     * @return string|array
     */
    public function getData()
    {
        return $this->getGeobaseData();
    }

    /**
     * функция получает данные по ip.
     * @return array - возвращает массив с данными
     */
    protected function getGeobaseData()
    {
        // получаем данные по ip
        $ch = curl_init('http://ipgeobase.ru:7020/geo?ip=' . $this->ip);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $string = curl_exec($ch);

        // если указана кодировка отличная от windows-1251, изменяем кодировку
        if ($this->charset != 'windows-1251' && function_exists('iconv')) {
            $string = iconv('windows-1251', $this->charset, $string);
        }

        $data = $this->parseString($string);
        return $data;
    }

    /**
     * функция парсит полученные в XML данные в случае, если на сервере не установлено расширение Simplexml
     * @param string $string
     * @return array - возвращает массив с данными
     */
    protected function parseString($string)
    {
        $params = array('inetnum', 'country', 'city', 'region', 'district', 'lat', 'lng');
        $data = $out = array();
        foreach ($params as $param) {
            if (preg_match('#<' . $param . '>(.*)</' . $param . '>#is', $string, $out)) {
                $data[$param] = trim($out[1]);
            }
        }
        return $data;
    }

    /**
     * функция определяет ip адрес по глобальному массиву $_SERVER
     * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
     * @return string ip-адрес
     */
    public function getIp()
    {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR', 'HTTP_X_REAL_IP');
        foreach ($keys as $key) {
            $ip = trim(strtok(filter_input(INPUT_SERVER, $key), ','));
            if ($this->isValidIp($ip)) {
                return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }
        }

        return null;
    }

    /**
     * функция для проверки валидности ip адреса
     * @param string $ip адрес в формате 1.2.3.4
     * @return boolean : true - если ip валидный, иначе false
     */
    public function isValidIp($ip)
    {
        return (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
}
