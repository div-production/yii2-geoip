<?php

namespace div\geoip;


use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\Cookie;

class Geo extends Component
{
    /**
     * сохранять ли город к cookie
     * @var bool
     */
    public $useCookie = true;

    /**
     * название cookie для сохранения идентификатора города
     * @var string
     */
    public $cookieName = 'city';

    /**
     * время жизни cookie (в днях)
     * @var int
     */
    public $cookieExpire = 1;

    /**
     * класс модели для работы с городами
     * @var string
     */
    public $cityClass;

    /**
     * название атрибута модели, в котором хранится название города
     * @var string
     */
    public $nameAttribute = 'name';

    /**
     * @var ActiveRecord
     */
    protected $city = false;

    /**
     * объект для работы с сервисом ipgeobase.ru
     * @var IpGeoBase
     */
    protected $base;

    public function init()
    {
        if (!$this->cityClass) {
            throw new InvalidConfigException('Необходимо указать класс для модели города');
        }

        $this->base = new IpGeoBase();
    }

    /**
     * определение города и если параметр useCookir установлен в true,
     * сохранение идентифкатора города в cookie
     * @param string|null $ip
     * @return ActiveRecord|null
     */
    public function getCity($ip = null)
    {
        if ($this->city !== false) {
            return $this->city;
        }

        /** @var ActiveRecord $cityClass */
        $cityClass = $this->cityClass;

        if ($this->useCookie && $cityId = $this->getCookie()) {
            if ($cityId == 'null') {
                return null;
            }
            $city = $cityClass::findOne($cityId);
            if ($city) {
                return $city;
            }
        }

        $data = $this->getData($ip);

        if (empty($data['city'])) {
            // если не удалось определить город
            $this->saveCookie('null');
            return null;
        }

        $city = $cityClass::find()->where([$this->nameAttribute => $data['city']])->one();
        if ($city) {
            $this->saveCookie($city->primaryKey);
            $this->city = $city;
            return $city;
        } else {
            // если города нет в базе
            $this->saveCookie('null');
            return null;
        }
    }

    /**
     * получение данных по текущему ip адресу
     * @return array|null
     * [
     *   'inetnum' => Диапазон адресов
     *   'country' => Страна
     *   'city' => Город
     *   'region' => Регион
     *   'district' => Район
     *   'lat' => Широта
     *   'lng' => Долгота
     * ]
     */
    public function getData($ip = null)
    {
        if (is_null($ip)) {
            $this->base->ip = $this->base->getIp();
        } else {
            $this->base->ip = $ip;
        }

        return $this->base->getData();
    }

    /**
     * сохранение идентификатора города в cookie
     * @param $cityId
     */
    protected function saveCookie($cityId)
    {
        if (!$this->useCookie) {
            return;
        }

        $cookie = new Cookie([
            'name' => $this->cookieName,
            'value' => $cityId,
            'expire' => time() + 3600 * 24 * $this->cookieExpire,
        ]);

        Yii::$app->response->cookies->add($cookie);
    }

    /**
     * получение сохранённого идентификатора города из cookie
     * @return int|null
     */
    protected function getCookie()
    {
        if (!$this->useCookie) {
            return null;
        }

        if ($cookie = Yii::$app->request->cookies->get($this->cookieName)) {
            if ($cookie->value == 'null') {
                return 'null';
            } else {
                return (int)$cookie->value;
            }
        } else {
            return null;
        }
    }
}
