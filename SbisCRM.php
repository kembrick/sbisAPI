<?php

namespace sbisAPI;
use Exception;

class SbisCRM
{

    private string $cookie;

    /**
     * @param string $login
     * @param string $password
     * @throws Exception
     */
    public function __construct(string $login, string $password)
    {
        $this->authenticateByPass($login, $password);
    }

    /**
     * Аутентификация по логину и паролю.
     * Получение идентификатора сессии.
     *
     * @param string $login
     * @param string $password
     * @return void
     * @throws Exception
     */
    private function authenticateByPass(string $login, string $password)
    {
        $params = [
            'Параметр' => [
                'Логин'  => $login,
                'Пароль' => $password,
            ],
        ];
        $cookie = $this->query('https://online.sbis.ru/auth/service/', 'СБИС.Аутентифицировать', $params);
        if (isset($cookie))
            $this->cookie = $cookie;
    }

    /**
     * Запрос к API СБИС
     *
     * @param string $url
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    private function query(string $url, string $method, array $params = [])
    {
        $headers = [
            'host: online.sbis.ru',
            'content-type: application/json; charset=UTF-8',
            'accept: application/json-rpc',
            'x-calledmethod: ' . $method,
        ];
        $data = [
            'jsonrpc'  => '2.0',
            'protocol' => 6,
            'method'   => $method,
        ];
        if (!empty($params))
            $data['params'] = $params;
        if (isset($this->cookie)) {
            $headers[] = 'x-sbissessionid: ' . $this->cookie;
        }
        $jsonData = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response)
            throw new Exception('Ошибка. Не получен ответ по адресу ' . $url, 500);
        $response = @json_decode($response, true);
        if (!$response)
            throw new Exception('Ошибка. Полученный ответ не в JSON-формате', 500);
        if (isset($response['error']) && $response['error'])
            throw new Exception('Ошибка "' . $response['error'] .'"');

        return $response['result'];
    }

    /**
     * Поиск клиента по номеру мобильного телефона
     *
     * @param string $phoneNumber
     * @return int|null ID клиента, если найден, в противном случае - null
     * @throws Exception
     */
    public function getCustomerByPhone(string $phoneNumber): ?int
    {
        $url = 'https://online.sbis.ru/service/';
        $method = 'CRMClients.GetCustomerByParams';
        $params = [
            'client_data' =>
                [
                    'd' => [
                        [
                            'd' => [[$phoneNumber, 'mobile_phone', null]],
                            's' => [
                                ['t' => 'Строка', 'n' => 'Value'],
                                ['t' => 'Строка', 'n' => 'Type'],
                                ['t' => 'Строка', 'n' => 'Priority']
                            ],
                            '_type' => 'recordset'
                        ]
                    ],
                    's' => [
                        ['n' => 'ContactData', 't' => 'Выборка']
                    ],
                    '_type' => 'record'
                ],
            'options' => null
        ];

        return $this->query($url, $method, $params)['d'][0];
    }

    /**
     * Создание нового клиента
     *
     * @param string $surname
     * @param string $name
     * @param string $patronymic
     * @param string $address
     * @param string $birthday
     * @param string $phone
     * @param string $email
     * @return int|null
     * @throws Exception
     */
    public function saveCustomer(string $surname, string $name, string $patronymic, string $address, string $birthday, string $phone, string $email): ?int
    {
        $url = 'https://online.sbis.ru/service/';
        $method = 'CRMClients.SaveCustomer';
        $params = [
            'CustomerData' => [
                'd' => [
                    $surname,
                    $name,
                    $patronymic,
                    $address,
                    $birthday,
                    [
                        'd' => [
                            [
                                $phone,
                                'mobile_phone',
                                null,
                            ],
                            [
                                $email,
                                'email'
                            ]
                        ],
                        's' => [
                            ['n' => 'Value', 't' => 'Строка'],
                            ['n' => 'Type', 't' => 'Строка'],
                            ['n' => 'Priority', 't' => 'Строка'],
                        ],
                        [
                            ['n' => 'Value', 't' => 'Строка'],
                            ['n' => 'Type', 't' => 'Строка'],
                        ],
                        '_type' => 'recordset'
                    ],
                ],
                's' => [
                    ['n' => 'Surname', 't' => 'Строка'],
                    ['n' => 'Name', 't' => 'Строка'],
                    ['n' => 'Patronymic', 't' => 'Строка'],
                    ['n' => 'Address', 't' => 'Строка'],
                    ['n' => 'BirthDay', 't' => 'Дата'],
                    ['n' => 'ContactData', 't' => 'Выборка'],
                ],
                '_type' => 'record',
                'f' => 0
            ],
        ];

        return $this->query($url, $method, $params);
    }

    /**
     * Создание сделки.
     * Если ее параметры не уникальны, система вернет ID с уже созданной ранее сделкой, обработка этого случая не реализована.
     * Сделка будет размещена в разделе Клиенты -> Источники продаж -> Не определено.
     *
     * @param string $name
     * @param string $phone
     * @param string $email
     * @param string $description
     * @return mixed|null
     * @throws Exception
     */
    public function saveLead(string $name, string $phone, string $email, string $description)
    {
        $url = 'https://online.sbis.ru/service/';
        $method = 'CRMLead.getCRMThemeByName';
        $params = [
            "НаименованиеТемы" => "Продажи"
        ];
        $themeId = $this->query($url, $method, $params)['d'][3];
        if (!isset($themeId))
            return null;

        $method = 'CRMLead.insertRecord';
        $params = [
            'Лид' => [
                'd' => [
                    $themeId,
                    [
                        'd' => [
                            $name,
                            $phone,
                            $email
                        ],
                        's' => [
                            ['n' => 'ФИО', 't' => 'Строка'],
                            ['n' => 'Телефон', 't' => 'Строка'],
                            ['n' => 'email', 't' => 'Строка']
                        ]
                    ],
                    $description
                ],
                's' => [
                    ['n' => 'Регламент', 't' => 'Число целое'],
                    ['n' => 'КонтактноеЛицо', 't' => 'Запись'],
                    ['n' => 'Примечание', 't' => 'Строка']
                ],
                '_type' => 'record',
                'f' => 0
            ]
        ];

        $result = $this->query($url, $method, $params);
        var_dump($result);
        return $result['d'][0];
    }

}