<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/Wysiwyg.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 9.
 */
namespace modules\wysiwyg;
class Wysiwyg extends \Module
{
    private static $_isEditorLoaded = false;

    /**
     * 에디터 클래스를 가져온다.
     *
     * @param string $id 위지윅에디터 고유값 (NULL 인 경우 신규로 생성하고, 값이 존재하는 경우 위지윅 본문 콘텐츠를 가져온다.)
     * @return \modules\wysiwyg\Editor $editor
     */
    public function getEditor(?string $id = null): \modules\wysiwyg\Editor
    {
        $editor = new \modules\wysiwyg\Editor($id);
        return $editor;
    }

    /**
     * 에디터를 사용하기 위한 필수요소를 불러온다.
     */
    public function preload(): void
    {
        if (self::$_isEditorLoaded == true) {
            return;
        }

        \Html::font('FontAwesome');
        \Html::script(self::getBase() . '/scripts/FroalaEditor.js');
        \Html::style(self::getBase() . '/styles/FroalaEditor.css');

        self::$_isEditorLoaded = true;
    }
}
