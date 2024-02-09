<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에디터에 삽입된 외부 리소스의 데이터를 반환한다.
 *
 * @file /modules/wysiwyg/processes/cors.get.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 9.
 */
if (defined('__IM__') == false) {
    exit();
}

// @todo 보안문제 해결
$path = preg_replace('/:\//', '://', $path);
$path = str_replace(' ', '+', $path);
echo file_get_contents($path);
exit();
