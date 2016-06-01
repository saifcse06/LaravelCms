<?php

function _getPrice($price, $oldPrice, $saleStatus, $saleFrom = null, $saleTo = null)
{
    $finalPrice = $oldPrice;
    if ($price > 0 && $price < $oldPrice && _isOnSale($saleStatus, $saleFrom, $saleTo)) {
        $finalPrice = $price;
    }
    return $finalPrice;
}

function _formatPrice($price)
{
    $price = number_format($price, 0, ',', '.');
    $locale = app()->getLocale();
    switch ($locale) {
        case 'vi_VN':{
                return $price . ' đ';
            }break;
        default:{
                return '$' . $price;
            }break;
    }
}

function _getSalePercent($oldPrice, $price, $abs = false)
{
    if (!$oldPrice) {
        return 0;
    }

    $down = $oldPrice - $price;
    $result = ceil(-($down / $oldPrice) * 100);
    if ($abs) {
        return abs($result) . '%';
    }

    return $result . '%';
}

function _isOnSale($saleStatus, $saleFrom = null, $saleTo = null)
{
    if ($saleStatus == 0) {
        return true;
    }

    if (!$saleTo) {
        return false;
    }

    $now = Carbon\Carbon::now();
    if ($now >= $saleTo || $saleFrom > $now) {
        return false;
    }

    return true;
}
