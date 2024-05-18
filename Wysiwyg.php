<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/Wysiwyg.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 20.
 */
namespace modules\wysiwyg;
class Wysiwyg extends \Module
{
    private static bool $_isEditorLoaded = false;
    private static \HTMLPurifier $_HtmlPurifier;

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
     * 원본 에디터 콘텐츠를 데이터베이스에 기록하기 위해 가공한다.
     *
     * @param object $origin 원본콘텐츠
     * @param \Component $component 콘텐츠를 생성한 컴포넌트객체
     * @param string $position_type 콘텐츠위치종류
     * @param string|int $position_id 콘텐츠위치고유값
     * @return \modules\wysiwyg\dtos\EditorContent $content 에디터 콘텐츠 객체
     */
    public function getEditorContent(
        object $origin,
        \Component $component,
        string $position_type,
        string|int $position_id
    ): \modules\wysiwyg\dtos\EditorContent {
        return new \modules\wysiwyg\dtos\EditorContent($origin, $component, $position_type, $position_id);
    }

    /**
     * 데이터베이스에 저장된 에디터 콘텐츠를 화면상에 출력하기 위한 콘텐츠로 변환한다.
     *
     * @param string $content 데이터베이스에 저장된 콘텐츠
     * @return \modules\wysiwyg\dtos\ViewerContent $content 뷰어 콘텐츠 객체
     */
    public function getViewerContent(string|object $content): \modules\wysiwyg\dtos\ViewerContent
    {
        return new \modules\wysiwyg\dtos\ViewerContent($content);
    }

    /**
     * XSS 공격방지 처리 클래스를 가져온다.
     *
     * @return \HTMLPurifier $HTMLPurifier
     */
    public function getHtmlPurifier(): \HTMLPurifier
    {
        if (isset(self::$_HtmlPurifier) == false) {
            require_once $this->getPath() . '/vendor/HTMLPurifier/HTMLPurifier.auto.php';

            $config = \HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', \Configs::cache());
            $config->set('Attr.EnableID', false);
            $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
            $config->set('Attr.ForbiddenClasses', ['fr-draggable', 'fr-deletable']);
            $config->set('AutoFormat.Linkify', false);
            $config->set('CSS.MaxImgLength', null);
            $config->set('CSS.AllowTricky', true);
            $config->set('CSS.Trusted', true);
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.FlashAllowFullScreen', true);
            $config->set('HTML.SafeEmbed', true);
            $config->set('HTML.SafeIframe', true);
            $config->set('HTML.SafeObject', true);
            $config->set('HTML.MaxImgLength', null);
            $config->set('HTML.ForbiddenAttributes', ['contenteditable']);
            $config->set('Output.FlashCompat', true);

            $iframe = explode("\n", str_replace(['.'], ['\\.'], $this->getConfigs('iframe')));
            $config->set('URI.SafeIframeRegexp', '#^(?:https?:)?//(?:' . implode('|', $iframe) . ')#');

            $def = $config->getHTMLDefinition(true);
            $def->addAttribute('img', 'usemap', 'CDATA');
            $def->addAttribute('img', 'data-attachment-id', 'Text');
            $def->addAttribute('a', 'data-attachment-id', 'Text');
            $def->addAttribute('a', 'data-module', 'Text');
            $def->addAttribute('i', 'data-type', 'Text');
            $def->addAttribute('i', 'data-extension', 'Text');

            $def->addElement('iframe', 'Block', 'Flow', 'Common', [
                'src' => 'URI#embedded',
                'width' => 'Length',
                'height' => 'Length',
                'name' => 'ID',
                'scrolling' => 'Enum#yes,no,auto',
                'frameborder' => 'Enum#0,1',
                'allowfullscreen' => 'Enum#,0,1',
                'webkitallowfullscreen' => 'Enum#,0,1',
                'mozallowfullscreen' => 'Enum#,0,1',
                'longdesc' => 'URI',
                'marginheight' => 'Pixels',
                'marginwidth' => 'Pixels',
            ]);

            $def->addElement('video', 'Block', 'Flow', 'Common', [
                'src' => 'URI',
                'data-attachment-id' => 'Text',
                'controls' => 'Bool',
            ]);

            self::$_HtmlPurifier = new \HTMLPurifier($config);
        }

        return self::$_HtmlPurifier;
    }

    /**
     * 에디터를 사용하기 위한 필수요소를 불러온다.
     */
    public function preload(): void
    {
        if (self::$_isEditorLoaded == true) {
            return;
        }

        \Html::font('bootstrap');
        \Html::script(self::getBase() . '/scripts/FroalaEditor.js');
        \Html::style(self::getBase() . '/styles/FroalaEditor.css');
        \Html::style('//fonts.googleapis.com/css2?family=Nanum+Gothic+Coding&display=swap');

        self::$_isEditorLoaded = true;
    }
}
