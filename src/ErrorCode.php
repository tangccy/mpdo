<?php

namespace tjn\pdo;

class ErrorCode {

    /**
     * 参数错误
     */
    const PARAM_ERR = 1;

    /**
     * 危险操作
     */
    const DANGER_ERR = 2;

    const TABLE_NOT_EXIST = 3;

    /**
     * 错误对应描述
     */
    const MESSAGE = [
        self::PARAM_ERR       => '参数错误',
        self::DANGER_ERR      => '危险操作',
        self::TABLE_NOT_EXIST => '表名不存在',
    ];
}