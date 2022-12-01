<?php
/**
 * 이 파일은 아이모듈 회원모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅클래스 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/Wysiwyg.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 11. 9.
 */
namespace modules\wysiwyg;
use \Html;
use \Modules;
use \ErrorData;
use \ErrorHandler;
use \Request;
use \Cache;
class Wysiwyg extends \Module
{
    /**
     * @var string $_id 위지윅에디터 고유값
     */
    private string $_id;

    /**
     * @var string $_title 위지윅에디터 textarea 의 title 속성
     */
    private string $_title;

    /**
     * @var string $_name 위지윅에디터 textarea 의 name 속성
     */
    private string $_name;

    /**
     * @var object $_templet 위지윅에디터 템플릿 설정
     */
    private object $_templet;

    /**
     * @var string $_width 위지윅에디터 너비
     */
    private string $_width;

    /**
     * @var string $_height 위지윅에디터 높이
     */
    private string $_height;

    /**
     * @var string $_placeholder 위지윅에디터 placeholder
     */
    private string $_placeholder;

    /**
     * @var string $_content 위지윅에디터 콘텐츠 내용
     */
    private string $_content;

    /**
     * @var bool $_required 위지윅에디터 필수입력 여부
     */
    private bool $_required = false;

    /**
     * @var bool $_disabled 위지윅에디터 비활성화 여부
     */
    private bool $_disabled = false;

    /**
     * @var string $_id 위지윅에디터 고유값
     */
    //    private ?ModuleAttachment $_attachment = null;
    //    private array $_hasLanguages = [];

    /**
     * 위지윅에디터를 호출한 컨텍스트를 설정한다.
     * 위지윅에디터에서 직접적으로 사용되지 않고, 위지윅에디터와 함께 사용되는 첨부파일모듈에서 사용된다.
     *
     * @param string $type 컨텍스트타입
     * @param string $target 컨텍스트대상
     * @return Wysiwyg $this
     */
    public function setContext(string $type, string $target): Wysiwyg
    {
        $this->_type = $type;
        $this->_target = $target;
        //		$this->_attachment->setModule($module);

        return $this;
    }

    /**
     * 위지윅에디터 입력폼의 이름을 설정한다.
     *
     * @param string $name 입력폼이름
     * @return Wysiwyg $this
     */
    public function setName(string $name): Wysiwyg
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * 위지윅에디터 입력폼의 placeholder 값을 설정한다.
     *
     * @param string $placeholder placeholder 텍스트
     * @return Wysiwyg $this
     */
    function setPlaceholder(string $placeholder): Wysiwyg
    {
        $this->_placeholder = $placeholder;

        return $this;
    }

    /**
     * 위지윅에디터 설정값을 초기화한다.
     */
    public function reset(): void
    {
        $this->_type = 'MODULE';
        $this->_target = 'wysiwyg';

        $this->_id = null;
        $this->_name = null;
        $this->_templet = null;
        $this->_placeholder = '';

        //$this->_title = null;
        $this->_content = '';
        /*
		$this->_required = false;
		$this->_hideButtons = array();
		$this->_fileUpload = true;
		$this->_imageUpload = true;
		$this->_files = array();
		*/
        $this->_required = false;
        $this->_disabled = false;
    }

    /**
     * 위지윅에디터를 사용하기 위한 필수요소를 미리 불러온다.
     */
    public function preload(): void
    {
        $group = 'modules.wysiwyg';

        /*
        
        if ($this->hasLanguage() == true) {
            $group .= '.' . iModules::getLanguage();
        }
        Cache::script($group, $this->getBase() . '/vendor/froala/wysiwyg-editor/js/froala_editor.min.js');
        if ($this->hasLanguage() == true) {
            Cache::script(
                $group,
                $this->getBase() . '/vendor/froala/wysiwyg-editor/js/languages/' . iModules::getLanguage() . '.js'
            );
        }
        Html::script(Cache::script($group));

        Cache::style('modules.wysiwyg', $this->getBase() . '/vendor/froala/wysiwyg-editor/css/froala_editor.css');
        Html::style(Cache::style('modules.wysiwyg'));
*/
        //$this->IM->loadWebFont('FontAwesome');
        //		Html::script($this->getDir().'/scripts/wysiwyg.js.php');
        //		Html::style($this->getDir().'//styles/wysiwyg.css.php?templet='.$this->_);
        //$this->loadCodeMirror();
    }

    /**
     * 위지윅에디터 레이아웃을 가져온다.
     *
     * @return string $html 위지윅에디터 HTML
     */
    public function getLayout(): string
    {
        $this->preload();

        $id = $this->_id ?? uniqid('wysiwyg-');
        $name = $this->_name ?? 'content';
        $width = $this->_width ?? '100%';
        $height = $this->_height ?? '200px';

        /*
		if ($this->_disabled == true) {
			if ($is_inline == true) return '<div id="'.$this->_id.'">'.$this->_content.'</div>';
			else return '<textarea id="'.$this->_id.'" name="'.$this->_name.'" title="'.($this->_title ?? $this->getText('text/title')).'" data-wysiwyg="FALSE"'.($this->_required == true ? ' data-required="required"' : '').' placeholder="'.$this->_placeholder.'" style="height:'.$this->_height.'px;">'.$this->_content.'</textarea>';
		}
		*/

        $content = $this->_content ?? '';
        $textarea = Html::element(
            'textarea',
            [
                'id' => $id,
                'name' => $name,
                'data-width' => $width,
                'data-height' => $height,
                'style' => 'width:100%; height:100px;',
            ],
            $content
        );

        return Html::tag(
            '<div data-role="module" data-module="wysiwyg">',
            '<p>동해물과 백두산이 마르고 닳도록</p>', //<p><img src="https://www.minitalk.io/attachment/view/1148/logo.png" style="width:200px; height:200px;"></p>',
            '</div>',
            $textarea
        );
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
