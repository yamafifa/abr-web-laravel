<?php

namespace App\UseCases;

/**
 * UpdateRsdt の結果
 */
enum UpdateRsdtStatus
{
    case Updated;
    case NotFound;
}
