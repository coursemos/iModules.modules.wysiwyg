<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/ModuleWysiwyg.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
class ModuleWysiwyg extends Module {
	private string $_type = 'MODULE';
	private string $_target = 'wysiwyg';
	
	private ?string $_id = null;
	private ?string $_title = null;
	private ?string $_name = null;
	private string $_theme = 'default';
	private int $_height = 300;
	private string $_placeholder = '';
	
	private string $_content = '';
	
	private bool $_required = false;
	private bool $_disabled = false;
	
	/**
	 * 위지윅에디터를 호출한 컨텍스트를 설정한다.
	 * 위지윅에디터에서 직접적으로 사용되지 않고, 위지윅에디터와 함께 사용되는 첨부파일모듈에서 사용된다.
	 *
	 * @param string $type 컨텍스트타입
	 * @param string $target 컨텍스트대상
	 * @return ModuleWysiwyg $this
	 */
	public function setContext(string $type,string $target):ModuleWysiwyg {
		$this->_type = $type;
		$this->_target = $target;
//		$this->_attachment->setModule($module);

		return $this;
	}
	
	/**
	 * 위지윅에디터 입력폼의 이름을 설정한다.
	 *
	 * @param string $name 입력폼이름
	 * @return ModuleWysiwyg $this
	 */
	public function setName(string $name):ModuleWysiwyg {
		$this->_name = $name;
		
		return $this;
	}
	
	/**
	 * 위지윅에디터 입력폼의 placeholder 값을 설정한다.
	 *
	 * @param string $placeholder placeholder 텍스트
	 * @return ModuleWysiwyg $this
	 */
	function setPlaceholder(string $placeholder):ModuleWysiwyg {
		$this->_placeholder = $placeholder;

		return $this;
	}
	
	/**
	 * 본문 내용을 설정한다.
	 *
	 * @param string $content 내용
	 * @param ?array $attachments 첨부파일배열
	 * @return ModuleWysiwyg $this
	 */
	public function setContent(string $content,?array $attachments=null):ModuleWysiwyg {
		$this->_content = $this->decodeContent($content,false);
//		$this->_attachments = $attachments ?? [];
		
		$this->_content = str_replace(array('&lt;','&gt;'),array('&amp;lt;','&amp;gt;'),$this->_content);

		return $this;
	}
	
	/**
	 * XSS 공격방지 처리 클래스를 가져온다.
	 *
	 * @return HTMLPurifier $HTMLPurifier
	 */
	public function getHTMLPurifier():HTMLPurifier {
		if ($this->_HTMLPurifier != null) return $this->_HTMLPurifier;

		require_once $this->getPath().'/classes/HTMLPurifier/HTMLPurifier.auto.php';

		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.SerializerPath',$this->IM->getModule('attachment')->getTempPath(true));
		$config->set('Attr.EnableID',false);
		$config->set('Attr.DefaultImageAlt','');
		$config->set('Attr.AllowedFrameTargets',array('_blank','_self'));
		$config->set('AutoFormat.Linkify',false);
		$config->set('HTML.MaxImgLength',null);
		$config->set('CSS.MaxImgLength',null);
		$config->set('CSS.AllowTricky',true);
		$config->set('Core.Encoding','UTF-8');
		$config->set('HTML.FlashAllowFullScreen',true);
		$config->set('HTML.SafeEmbed',true);
		$config->set('HTML.SafeIframe',true);
		$config->set('HTML.SafeObject',true);
		$config->set('Output.FlashCompat',true);

		$iframe = explode("\n",str_replace(array('.'),array('\\.'),$this->getConfig('iframe')));
		$config->set('URI.SafeIframeRegexp', '#^(?:https?:)?//(?:'.implode('|',$iframe).')#');

		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('img','usemap','CDATA');

		$map = $def->addElement('map','Block','Flow','Common',array('name'=>'CDATA'));
		$map->excludes = array('map'=>true);

		$area = $def->addElement('area','Block','Empty','Common',array(
			'name'=>'CDATA','alt'=>'Text','coords'=>'CDATA','accesskey'=>'Character','nohref'=>new HTMLPurifier_AttrDef_Enum(array('nohref')),'href'=>'URI','shape'=>new HTMLPurifier_AttrDef_Enum(array('rect','circle','poly','default')),'tabindex'=>'Number','target'=>new HTMLPurifier_AttrDef_Enum(array('_blank','_self','_target','_top'))
		));
		$area->excludes = array('area'=>true);

		$def->addElement('iframe','Inline','Flow','Common',array(
			'src'=>'URI#embedded','width'=>'Length','height'=>'Length','name'=>'ID','scrolling'=>'Enum#yes,no,auto','frameborder'=>'Enum#0,1','allowfullscreen'=>'Enum#,0,1','webkitallowfullscreen'=>'Enum#,0,1','mozallowfullscreen'=>'Enum#,0,1','longdesc'=>'URI','marginheight'=>'Pixels','marginwidth'=>'Pixels'
		));

		$this->_HTMLPurifier = new HTMLPurifier($config);

		return $this->_HTMLPurifier;
	}
	
	/**
	 * 위지윅에디터 내용 출력을 위해 내용을 정리한다.
	 * 공격코드제거(AntiXSS) 및 첨부파일 정리, 스타일시트 적용
	 *
	 * @param string $content 위지윅에디터 원본내용
	 * @param bool $is_purify 공격코드를 제거할지 여부 (기본값 : true)
	 * @param bool $is_site_url 사이트주소를 반드시 포함할지 여부 (기본값 : false)
	 * @return string $content 출력을 위한 위지윅에디터 내용
	 */
	public function decodeContent(string $content,bool $is_purify=true,bool $is_site_url=false):string {
		if (preg_match_all('/<img([^>]*)data-idx="([0-9]+)"([^>]*)>/',$content,$match,PREG_SET_ORDER) == true) {
			for ($i=0, $loop=count($match);$i<$loop;$i++) {
				
				$file = null;//$this->IM->getModule('attachment')->getFileInfo($match[$i][2],false,$is_site_url);
				if ($file != null) {
					$match[$i][1] = preg_replace("!src=\"(.*?)\"!is","",$match[$i][1]);
					$match[$i][3] = preg_replace("!src=\"(.*?)\"!is","",$match[$i][3]);
					$image = '<a href="'.$file->path.'" target="_blank"><img'.$match[$i][1].'data-idx="'.$match[$i][2].'" src="'.$file->path.'"'.$match[$i][3].'></a>';
				} else {
					$image = '';
				}
				$content = str_replace($match[$i][0],$image,$content);
			}
		}

		if (preg_match_all('/<a([^>]*)data-idx="([0-9]+)"([^>]*)>/',$content,$match,PREG_SET_ORDER) == true) {
			for ($i=0, $loop=count($match);$i<$loop;$i++) {
				$file = null;//$this->IM->getModule('attachment')->getFileInfo($match[$i][2],false,$is_site_url);
				if ($file != null) {
					$match[$i][1] = preg_replace("!href=\"(.*?)\"!is","",$match[$i][1]);
					$match[$i][3] = preg_replace("!href=\"(.*?)\"!is","",$match[$i][3]);
					$link = '<a'.$match[$i][1].'data-idx="'.$match[$i][2].'" href="'.$file->download.'"'.$match[$i][3].'>';
				} else {
					$link = '';
				}
				$content = str_replace($match[$i][0],$link,$content);
			}
		}

		if ($is_purify == true) {
			$content = $this->getHTMLPurifier()->purify($content);
			$content = PHP_EOL.'<div data-role="wysiwyg-content">'.$content.'</div>'.PHP_EOL;
		}

		return $content;
	}
	
	/**
	 * 위지윅에디터를 사용하기 위한 필수요소를 미리 불러온다.
	 */
	public function preload():void {
		//$this->IM->loadWebFont('FontAwesome');
		Html::script($this->getDir().'/scripts/wysiwyg.js.php');
		Html::style($this->getDir().'//styles/wysiwyg.css.php?theme='.$this->_theme);
		//$this->loadCodeMirror();
	}
	
	/**
	 * 위지윅에디터 레이아웃을 가져온다.
	 *
	 * @param bool $is_inline 인라인모드로 출력할지 여부
	 * @return string $html 위지윅에디터 HTML
	 */
	public function getLayout(bool $is_inline=false):string {
		$this->preload();

		$this->_id ??= uniqid('wysiwyg-');
		$this->_name ??= 'content';
		/*
		if ($this->_disabled == true) {
			if ($is_inline == true) return '<div id="'.$this->_id.'">'.$this->_content.'</div>';
			else return '<textarea id="'.$this->_id.'" name="'.$this->_name.'" title="'.($this->_title ?? $this->getText('text/title')).'" data-wysiwyg="FALSE"'.($this->_required == true ? ' data-required="required"' : '').' placeholder="'.$this->_placeholder.'" style="height:'.$this->_height.'px;">'.$this->_content.'</textarea>';
		}
		*/
		$wysiwyg = Html::tag('<div data-role="module" data-module="wysiwyg">');

		if ($is_inline == true) {
			$wysiwyg.= '<div id="'.$this->_id.'" name="'.$this->_name.'" data-wysiwyg="TRUE" data-type="'.$this->_type.'" data-target="'.$this->_target.'">'.$this->_content.'</div>'.PHP_EOL;
		} else {
			$wysiwyg.= Html::tag(
				'<textarea id="'.$this->_id.'" name="'.$this->_name.'" title="내용" data-role="wysiwyg" data-type="'.$this->_type.'" data-target="'.$this->_target.'" style="height:'.$this->_height.'px"'.($this->_required == true ? ' data-required="required"' : '').'  placeholder="'.$this->_placeholder.'">'.$this->_content.'</textarea>'
			);
		}
		$wysiwyg.= Html::tag(
			'</div>',
			'<script>$(document).ready(function() { $("#'.$this->_id.'").wysiwyg(); });</script>'
		);
		/*
		if (is_array($this->_files) == true) {
			foreach ($this->_files as $file) {
				$wysiwyg.= '<input type="hidden" name="'.$this->_name.'_files[]" value="'.Format::encoder($file).'">'.PHP_EOL;
			}
		}
		*/
		$this->reset();

		return $wysiwyg;
	}
	
	/**
	 * 위지윅에디터 설정값을 초기화한다.
	 */
	public function reset():void {
		$this->_type = 'MODULE';
		$this->_target = 'wysiwyg';
		
		$this->_id = null;
		$this->_name = null;
		$this->_theme = 'default';
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
	 * 위지윅에디터 레이아웃을 출력한다.
	 *
	 * @param bool $is_inline 인라인모드로 출력할지 여부
	 */
	public function doLayout(bool $is_inline=false) {
		echo $this->getLayout($is_inline);
	}
}
?>