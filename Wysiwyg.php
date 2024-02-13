<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/Wysiwyg.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
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
     * @param bool $is_purifier HTML 화이트리스트 적용여부
     * @param bool $is_full_url 본문 첨부파일 경로를 도메인을 포함한 전체 URL 을 사용할지 여부
     * @return string $content 콘텐츠
     */
    public function getViewerContent(string $content, bool $is_purifier = true, bool $is_full_url = false): string
    {
        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = \Modules::get('attachment');

        if (
            preg_match_all('/<img[^>]*data-attachment-id="(.*?)"[^>]*>/i', $content, $matches, PREG_SET_ORDER) == true
        ) {
            foreach ($matches as $matched) {
                $origin = $matched[0];
                $attachment_id = $matched[1];
                $attachment = $mAttachment->getAttachment($attachment_id);
                if ($attachment === null) {
                    $content = str_replace($origin, '', $content);
                }

                if (preg_match('/class="(.*?)"/i', $origin, $class) == true) {
                    $class = $class[1];
                } else {
                    $class = null;
                }

                if (preg_match('/style="(.*?)"/i', $origin, $style) == true) {
                    $style = $style[1];
                } else {
                    $style = null;
                }

                $insert = \Html::element('img', [
                    'src' => $attachment->getUrl('view', $is_full_url),
                    'data-attachment-id' => $attachment_id,
                    'class' => $class,
                    'style' => $style,
                ]);

                $content = str_replace($origin, $insert, $content);
            }
        }

        if (
            preg_match_all('/<a[^>]*data-attachment-id="(.*?)"[^>]*>.*?<\/a>/i', $content, $matches, PREG_SET_ORDER) ==
            true
        ) {
            foreach ($matches as $matched) {
                $origin = $matched[0];
                $attachment_id = $matched[1];
                $attachment = $mAttachment->getAttachment($attachment_id);
                if ($attachment === null) {
                    $content = str_replace($origin, '', $content);
                }

                $insert = \Html::element(
                    'a',
                    [
                        'href' => $attachment->getUrl('download', $is_full_url),
                        'data-attachment-id' => $attachment_id,
                        'data-module' => 'attachment',
                        'download' => $attachment->getName(),
                    ],
                    \Html::element(
                        'i',
                        [
                            'data-type' => $attachment->getType(),
                            'data-extension' => $attachment->getExtension(),
                        ],
                        $attachment->getExtension()
                    ) .
                        \Html::element('b', null, $attachment->getName()) .
                        \Html::element('small', null, \Format::size($attachment->getSize()))
                );

                $content = str_replace($origin, $insert, $content);
            }
        }

        if ($is_purifier === true) {
            $content = $this->getHtmlPurifier()->purify($content);
        }

        return $content;
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
            $config->set('AutoFormat.Linkify', false);
            $config->set('HTML.MaxImgLength', null);
            $config->set('CSS.MaxImgLength', null);
            $config->set('CSS.AllowTricky', true);
            $config->set('CSS.Trusted', true);
            $config->set('Core.Encoding', 'UTF-8');
            $config->set('HTML.FlashAllowFullScreen', true);
            $config->set('HTML.SafeEmbed', true);
            $config->set('HTML.SafeIframe', true);
            $config->set('HTML.SafeObject', true);
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

            $def->addElement('iframe', 'Inline', 'Flow', 'Common', [
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

        \Html::font('FontAwesome');
        \Html::script(self::getBase() . '/scripts/FroalaEditor.js');
        \Html::style(self::getBase() . '/styles/FroalaEditor.css');

        self::$_isEditorLoaded = true;
    }
}
