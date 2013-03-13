<?php
function smarty_modifier_formattime($time) {
    $now = time();
    $then = intval($time);
    $dur = $now - $then;
    if ($dur < 10)
        return '刚刚';
    if ($dur < 60)
        return $dur . '秒前';
    if ($dur < 3600)
        return floor($dur / 60) . '分钟前';
    $thenDate = strtotime(date("Ymd", $then));
    if ($thenDate == strtotime(date("Ymd", $now)))
        return '今天' . date("H:i", $then);
    if ($thenDate == strtotime(date("Ymd", $now - 86400)))
        return '昨天' . date("H:i", $then);
    if (date("Y", $then) == date("Y", $now))
        return date("m-d H:i", $then);
    return date("Y-m-d H:i", $then);
}