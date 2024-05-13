<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에디터에 의해 편집되어 저장된 콘텐츠를 화면상에 출력하기 위한 콘텐츠 구조체를 정의한다.
 *
 * @file /modules/wysiwyg/dtos/ViewerContent.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 13.
 */
namespace modules\wysiwyg\dtos;
class ViewerContent
{
    /**
     * @var ?object $_origin 저장된 원본 콘텐츠
     */
    private ?object $_origin = null;

    /**
     * @var string $_content 본문내용
     */
    private string $_content;

    /**
     * @var string[] $_attachments 본문에 첨부된 첨부파일 고유값
     */
    private array $_attachments;

    /**
     * 에디터 콘텐츠 구조체를 정의한다.
     *
     * @param object|string $origin 데이터베이스에 저장된 콘텐츠 원본
     */
    public function __construct(object|string $origin)
    {
        if (is_string($origin) == true) {
            $json = json_decode($origin);
            if ($json === null || isset($json->content) == false) {
                $this->_origin = new \stdClass();
                $this->_origin->content = $origin;
            } else {
                $this->_origin = $json;
            }
        } else {
            $this->_origin = $origin;
        }
    }

    /**
     * 본문 콘텐츠를 처리한다.
     */
    private function parse()
    {
        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = \Modules::get('attachment');
        $content = $this->_origin?->content ?? '';
        $attachments = [];

        if (
            preg_match_all('/<img[^>]*data-attachment-id="(.*?)"[^>]*>/i', $content, $matches, PREG_SET_ORDER) == true
        ) {
            foreach ($matches as $matched) {
                $origin = $matched[0];
                $attachment_id = $matched[1];
                $attachment = $mAttachment->getAttachment($attachment_id);
                if ($attachment === null) {
                    $content = str_replace($origin, '', $content);
                    continue;
                }

                if (in_array($attachment_id, $attachments) == false) {
                    $attachments[] = $attachment_id;
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
                    'src' => $attachment->getUrl('view', true),
                    'data-attachment-id' => $attachment_id,
                    'class' => $class,
                    'style' => $style,
                ]);

                $content = str_replace($origin, $insert, $content);
            }
        }

        if (
            preg_match_all(
                '/<video[^>]*data-attachment-id="(.*?)"[^>]*>.*?<\/video>/i',
                $content,
                $matches,
                PREG_SET_ORDER
            ) == true
        ) {
            foreach ($matches as $matched) {
                $origin = $matched[0];
                $attachment_id = $matched[1];
                $attachment = $mAttachment->getAttachment($attachment_id);
                if ($attachment === null) {
                    $content = str_replace($origin, '', $content);
                    continue;
                }

                if (in_array($attachment_id, $attachments) == false) {
                    $attachments[] = $attachment_id;
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

                $insert = \Html::element(
                    'video',
                    [
                        'src' => $attachment->getUrl('view', true),
                        'data-attachment-id' => $attachment_id,
                        'controls' => '1',
                        'class' => $class,
                        'style' => $style,
                    ],
                    ''
                );

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
                    continue;
                }

                if (in_array($attachment_id, $attachments) == false) {
                    $attachments[] = $attachment_id;
                }

                $attributes = [
                    'href' => $attachment->getUrl('download', true),
                    'data-attachment-id' => $attachment_id,
                    'data-module' => 'attachment',
                    'download' => $attachment->getName(),
                    'class' => 'fr-deletable',
                    'contenteditable' => 'false',
                ];

                $insert = \Html::element(
                    'a',
                    $attributes,
                    \Html::element(
                        'i',
                        [
                            'data-type' => $attachment->getType(),
                            'data-extension' => $attachment->getExtension(),
                        ],
                        $attachment->getExtension()
                    ) .
                        \Html::element('span', null, $attachment->getName()) .
                        \Html::element('small', null, \Format::size($attachment->getSize()))
                );

                $content = str_replace($origin, $insert, $content);
            }
        }

        $oAttachments = array_filter($this->_origin?->attachments ?? [], function ($attachment_id) use ($attachments) {
            return in_array($attachment_id, $attachments);
        });
        foreach ($attachments as $attachment_id) {
            if (in_array($attachment_id, $oAttachments) == false) {
                $oAttachments[] = $attachment_id;
            }
        }

        $this->_content = $content;
        $this->_attachments = $oAttachments;
    }

    /**
     * 데이터베이스에 저장하기 위해 가공한 콘텐츠를 가져온다.
     *
     * @param bool $is_purifier HTML 화이트리스트 적용여부
     * @param bool $is_full_url 본문 첨부파일 경로를 도메인을 포함한 전체 URL 을 사용할지 여부
     * @return string $content
     */
    public function getContent(bool $is_purifier = true, bool $is_full_url = false): string
    {
        if (isset($this->_content) == false) {
            $this->parse();
        }

        $content = $this->_content;
        if ($is_full_url == false) {
            $content = str_replace(\Domains::get()->getUrl(false), '', $content);
        }

        if ($is_purifier === true) {
            /**
             * @var \modules\wysiwyg\Wysiwyg $mWysiwyg
             */
            $mWysiwyg = \Modules::get('wysiwyg');
            return $mWysiwyg->getHtmlPurifier()->purify($content);
        }

        return $content;
    }

    /**
     * 본문 또는 업로더에 첨부된 첨부파일 고유값 배열을 가져온다.
     *
     * @return string[] $attachments
     */
    public function getAttachments(): array
    {
        if (isset($this->_attachments) == false) {
            $this->parse();
        }

        return $this->_attachments;
    }

    /**
     * 데이터베이스에 저장하기 위해 가공한 콘텐츠와 첨부파일목록을 JSON 으로 가져온다.
     *
     * @return object $json
     */
    public function getJson(): object
    {
        $json = new \stdClass();
        $json->content = $this->getContent(false, false);
        $json->attachments = $this->getAttachments();

        return $json;
    }
}
