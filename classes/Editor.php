<?php
/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에디터 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/classes/Editor.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
namespace modules\wysiwyg;
class Editor
{
    /**
     * @var string $_id 위지윅에디터 고유값
     */
    private string $_id;

    /**
     * @var string $_name 입력폼이름
     */
    private string $_name = 'content';

    /**
     * @var string $_width 위지윅에디터 너비 (% 또는 px 단위를 포함하여 설정)
     */
    private string $_width = '100%';

    /**
     * @var int $_height 위지윅에디터 높이 (px 단위)
     */
    private int $_height = 300;

    /**
     * @var ?string $_maxHeight 위지윅에디터 최대높이 (px 단위, NULL 인 경우 제한하지 않음)
     */
    private ?int $_maxHeight = null;

    /**
     * @var ?string $_placeholder 위지윅에디터 placeholder
     */
    private ?string $_placeholder = null;

    /**
     * @var string $_content 위지윅에디터 콘텐츠 내용
     */
    private ?string $_content = null;

    /**
     * @var bool $_imageUpload 이미지 업로드 여부
     */
    private bool $_imageUpload = true;

    /**
     * @var bool $_fileUpload 파일 업로드 여부
     */
    private bool $_fileUpload = true;

    /**
     * @var bool $_videoUpload 비디오 업로드 여부
     */
    private bool $_videoUpload = true;

    /**
     * @var bool $_required 위지윅에디터 필수입력 여부
     */
    private bool $_required = false;

    /**
     * @var bool $_disabled 위지윅에디터 비활성화 여부
     */
    private bool $_disabled = false;

    /**
     * @var \modules\attachment\Uploader $_uploader 에디터에 사용할 업로더 클래스
     */
    private ?\modules\attachment\Uploader $_uploader = null;

    /**
     * 위지윅에디터 클래스를 생성한다.
     *
     * @param ?string $id 위지윅에디터 고유값 (NULL 인 경우 신규로 생성하고, 값이 존재하는 경우 위지윅 본문 콘텐츠를 가져온다.)
     */
    public function __construct(?string $id = null)
    {
        $this->_id = $id ?? \UUID::v4();
    }

    /**
     * 에디터 고유값을 가져온다.
     *
     * @return string $id
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * 위지윅에디터 입력폼의 이름을 설정한다.
     *
     * @param string $name 입력폼이름
     * @return \modules\wysiwyg\Editor $this
     */
    public function setName(string $name): \modules\wysiwyg\Editor
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * 위지윅에디터 본문내용을 설정한다.
     *
     * @param ?string $content 본문내용
     * @return \modules\wysiwyg\Editor $this
     */
    public function setContent(?string $content): \modules\wysiwyg\Editor
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * 위지윅에디터 너비를 지정한다.
     *
     * @param string $width
     * @return \modules\wysiwyg\Editor $this
     */
    public function setWidth(string $width): \modules\wysiwyg\Editor
    {
        $this->_width = $width;
        return $this;
    }

    /**
     * 위지윅에디터 높이를 지정한다.
     *
     * @param int $height 높이 (px 단위)
     * @param bool $is_max_height 최대높이도 같이 설정할지 여부 (에디터 높이가 변하지 않는다.)
     * @return \modules\wysiwyg\Editor $this
     */
    public function setHeight(int $height, bool $is_max_height = false): \modules\wysiwyg\Editor
    {
        $this->_height = $height;
        if ($is_max_height == true) {
            $this->_maxHeight = $height;
        }
        return $this;
    }

    /**
     * 위지윅에디터 최대높이를 지정한다.
     *
     * @param int $maxHeight 최대높이 (px 단위, NULL 인 경우 제한하지 않음)
     * @param bool $is_max_height 최대높이도 같이 설정할지 여부 (에디터 높이가 변하지 않는다.)
     * @return \modules\wysiwyg\Editor $this
     */
    public function setMaxHeight(?int $maxHeight = null): \modules\wysiwyg\Editor
    {
        $this->_maxHeight = $maxHeight;
        return $this;
    }

    /**
     * 위지윅에디터 입력폼의 placeholder 값을 설정한다.
     *
     * @param string $placeholder placeholder 텍스트
     * @return \modules\wysiwyg\Editor $this
     */
    public function setPlaceholder(?string $placeholder = null): \modules\wysiwyg\Editor
    {
        $this->_placeholder = $placeholder;
        return $this;
    }

    /**
     * 이미지 업로드 허용 여부를 설정한다.
     *
     * @param bool $imageUpload 업로드 허용여부
     * @return \modules\wysiwyg\Editor $this
     */
    public function setImageUpload(bool $imageUpload): \modules\wysiwyg\Editor
    {
        $this->_imageUpload = $imageUpload;
        return $this;
    }

    /**
     * 파일 업로드 허용 여부를 설정한다.
     *
     * @param bool $fileUpload 파일 허용여부
     * @return \modules\wysiwyg\Editor $this
     */
    public function setFileUpload(bool $fileUpload): \modules\wysiwyg\Editor
    {
        $this->_fileUpload = $fileUpload;
        return $this;
    }

    /**
     * 비디오 업로드 허용 여부를 설정한다.
     *
     * @param bool $videoUpload 파일 허용여부
     * @return \modules\wysiwyg\Editor $this
     */
    public function setVideoUpload(bool $videoUpload): \modules\wysiwyg\Editor
    {
        $this->_videoUpload = $videoUpload;
        return $this;
    }

    /**
     * 에디터에 사용할 업로더 클래스를 지정한다.
     * 업로더가 지정되어 있지 않은 경우 에디터 자체적으로 파일 업로드를 처리한다.
     *
     * @param \modules\attachment\Uploader $uploader
     * @return \modules\wysiwyg\Editor $this
     */
    public function setUploader(?\modules\attachment\Uploader $uploader = null): \modules\wysiwyg\Editor
    {
        $this->_uploader = $uploader;
        $uploader?->setRender(false);
        return $this;
    }

    /**
     * 업로더 클래스를 가져온다.
     *
     * @return \modules\attachment\Uploader $uploader
     */
    public function getUploader(): ?\modules\attachment\Uploader
    {
        return $this->_uploader;
    }

    /**
     * 위지윅에디터 레이아웃을 가져온다.
     *
     * @return string $html 위지윅에디터 HTML
     */
    public function getLayout(): string
    {
        /**
         * @var \modules\wysiwyg\Wysiwyg $mWysiwyg
         */
        $mWysiwyg = \Modules::get('wysiwyg');
        $mWysiwyg->preload();

        $properties = [
            'name' => $this->_name,
            'data-id' => $this->_id,
            'data-role' => 'editor',
            'data-width' => $this->_width,
            'data-height' => $this->_height,
            'data-placeholder' => $this->_placeholder ?? '',
            'data-image-upload' => $this->_imageUpload == true ? 'true' : 'false',
            'data-file-upload' => $this->_fileUpload == true ? 'true' : 'false',
            'data-video-upload' => $this->_videoUpload == true ? 'true' : 'false',
            'data-required' => $this->_required == true ? 'true' : 'false',
            'data-disabled' => $this->_disabled == true ? 'true' : 'false',
        ];

        if ($this->_maxHeight !== null) {
            $properties['data-max-height'] = $this->_maxHeight;
        }

        if ($this->_imageUpload == true || $this->_fileUpload == true) {
            if ($this->getUploader() === null) {
                /**
                 * @var \modules\attachment\Attachment $mAttachment
                 */
                $mAttachment = \Modules::get('attachment');
                $template = new \stdClass();
                $template->name = 'hidden';
                $uploader = $mAttachment
                    ->getUploader()
                    ->setName('attachments')
                    ->setTemplate($template);
                $this->setUploader($uploader);
            }
        } else {
            $this->setUploader(null);
        }

        if ($this->getUploader() !== null) {
            $properties['data-uploader-id'] = $this->getUploader()->getId();
            $properties['data-uploader-name'] = $this->getUploader()->getName();
        }

        $textarea = \Html::element('textarea', $properties, $this->_content ?? '');

        $wysiwyg = \Html::element(
            'div',
            [
                'data-role' => 'module',
                'data-module' => 'wysiwyg',
                'data-field' => 'editor',
                'data-name' => $this->_name,
            ],
            $textarea
        );

        if ($this->getUploader() !== null) {
            $wysiwyg .= PHP_EOL . $this->getUploader()->getLayout();
        }

        return $wysiwyg;
    }

    /**
     * 위지윅에디터 레이아웃을 출력한다.
     *
     * @param bool $is_inline 인라인모드로 출력할지 여부
     */
    public function doLayout(bool $is_inline = false)
    {
        echo $this->getLayout($is_inline);
    }
}
