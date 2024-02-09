/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/scripts/Wysiwyg.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 9.
 */
var modules;
(function (modules) {
    let wysiwyg;
    (function (wysiwyg) {
        class Wysiwyg extends Module {
            static editors = new WeakMap();
            /**
             * 모듈의 DOM 이벤트를 초기화한다.
             * 해당 DOM 내부에 에디터를 사용하는 <textarea> 필드가 존재할 경우
             * 에디터를 활성화한다.
             *
             * @param {Dom} $dom - 모듈 DOM 객체
             */
            init($dom) {
                if (Html.get('textarea[data-role=editor]', $dom).getEl() !== null) {
                    this.getEditor(Html.get('textarea[data-role=editor]', $dom));
                }
            }
            /**
             * <textarea> DOM 객체를 통해 에디터를 가져온다.
             *
             * @param {Dom} $textarea - <textarea> DOM 객체
             * @return {modules.wysiwyg.Editor} editor - 에디터 클래스
             */
            getEditor($textarea) {
                const textarea = $textarea.getEl();
                if (textarea instanceof HTMLTextAreaElement) {
                    if (modules.wysiwyg.Wysiwyg.editors.has(textarea) == false) {
                        modules.wysiwyg.Wysiwyg.editors.set(textarea, new modules.wysiwyg.Editor($textarea));
                    }
                    return modules.wysiwyg.Wysiwyg.editors.get(textarea);
                }
                return null;
            }
        }
        wysiwyg.Wysiwyg = Wysiwyg;
        class Editor {
            id;
            $textarea;
            editor;
            uploader;
            /**
             * <textarea> DOM 객체를 이용하여 에디터를 활성화한다.
             *
             * @param {Dom} $textarea - <textarea> DOM 객체
             * @param {modules.wysiwyg.Editor.Properties} properties - 설정 (DOM 객체에 설정된 값보다 우선시 됩니다.)
             */
            constructor($textarea, properties = null) {
                this.$textarea = $textarea;
                this.id = this.$textarea.getAttr('data-id');
                properties ??= {};
                properties.toolbarSticky = false;
                properties.toolbarButtons ??= [
                    'html',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    '|',
                    'paragraphFormat',
                    'fontSize',
                    'color',
                    '|',
                    'align',
                    'formatOL',
                    'formatUL',
                    '|',
                    'insertLink',
                    'insertTable',
                    'insertImage',
                    'insertFile',
                    'insertVideo',
                    'insertCode',
                    'emoticons',
                    'quote',
                ];
                properties.imageDefaultWidth = 0;
                properties.imageAddNewLine = true;
                properties.imageEditButtons = [
                    'imageAlign',
                    'imageLink',
                    'linkOpen',
                    'linkEdit',
                    'linkRemove',
                    'imageDisplay',
                    'imageAlt',
                    'imageSize',
                    '|',
                    'imageRemove',
                ];
                properties.videoUpload = false;
                properties.heightMin ??= $textarea.getAttr('data-height') ?? 100;
                properties.heightMax ??= $textarea.getAttr('data-max-height') ?? null;
                properties.imageCORSProxy = '/module/wysiwyg/process/cors/';
                properties.codeBeautifierOptions = {
                    end_with_newline: true,
                    indent_inner_html: true,
                    extra_liners: [],
                    brace_style: 'expand',
                    indent_char: '\t',
                    indent_size: 1,
                    wrap_line_length: 0,
                };
                this.editor = new modules.wysiwyg.FroalaEditor($textarea.getEl(), properties);
                this.editor.render().then(($editor) => {
                    if ($editor === null) {
                        return;
                    }
                    if ($textarea.getAttr('data-uploader-id')) {
                        const attachment = Modules.get('attachment');
                        this.uploader = attachment.getUploader(Html.get('div[data-role=uploader][data-id="' + $textarea.getAttr('data-uploader-id') + '"]'));
                        this.uploader.setEditor(this);
                    }
                    $editor.on('froalaEditor.image.beforeUpload', (e, editor, files, $image_placeholder) => {
                        const images = this.uploader.add(files);
                        for (const image of images) {
                            const $img = this.addImagePlaceholder(image, editor, $image_placeholder);
                            $img.addClass('fr-uploading');
                            if ($img.next().is('br')) {
                                $img.next().remove();
                            }
                            editor.placeholder.refresh();
                            editor.edit.off();
                        }
                        return false;
                    });
                    this.uploader.addEvent('update', (file) => {
                        const $placeholder = this.editor.$('img[data-index="' + file.index + '"]', this.editor.get().$el);
                        if ($placeholder.length == 1) {
                            $placeholder.attr('data-id', file.attachment.id);
                            if (file.status == 'COMPLETE') {
                                $placeholder.attr('src', file.attachment.view);
                                this.editor.get().image.insert(file.attachment.view, false, {
                                    'id': file.attachment.id,
                                }, $placeholder);
                            }
                        }
                    });
                });
            }
            /**
             * 에디터 고유값을 가져온다.
             *
             * @return {string} id
             */
            getId() {
                return this.id;
            }
            /**
             * 파일을 삽입한다.
             *
             * @param {modules.attachment.Uploader.File} file
             */
            insertFile(file) {
                console.log('insertFile', file, ['image', 'svg', 'icon'].includes(file.attachment.type));
                if (['image', 'svg', 'icon'].includes(file.attachment.type) == true) {
                    this.editor.get().image.insert(file.attachment.view, false, {
                        'id': file.attachment.id,
                    });
                }
            }
            /**
             * 이미지를 업로드하고 있는 도중 에디터에 업로드중인 이미지의 Placeholder 를 추가한다.
             *
             * @param {modules.attachment.Uploader.File} image - 업로드할 이미지파일 객체
             * @param {any} editor - 이미지가 업로드되는 에디터 객체
             */
            addImagePlaceholder(image, editor, $image_placeholder) {
                if ($image_placeholder) {
                    $image_placeholder.attr('data-id', image.attachment.id);
                    $image_placeholder.attr('data-index', image.index);
                    editor.edit.on();
                    editor.undo.saveStep();
                    $image_placeholder.data('fr-old-src', $image_placeholder.attr('src'));
                    $image_placeholder.attr('src', image.attachment.view);
                    return $image_placeholder;
                }
                else {
                    const $image = Html.create('img', {
                        src: image.attachment.view,
                        'data-id': image.attachment.id,
                        'data-index': image.index.toString(),
                    });
                    const $img = this.editor.$($image.getEl());
                    const _align = editor.opts.imageDefaultAlign;
                    const _display = editor.opts.imageDefaultDisplay;
                    if (!editor.opts.htmlUntouched && editor.opts.useClasses) {
                        $img.removeClass('fr-fil fr-fir fr-dib fr-dii');
                        if (_align) {
                            $img.addClass('fr-fi' + _align[0]);
                        }
                        if (_display) {
                            $img.addClass('fr-di' + _display[0]);
                        }
                    }
                    else {
                        if (_display == 'inline') {
                            $img.css({
                                display: 'inline-block',
                                verticalAlign: 'bottom',
                                margin: editor.opts.imageDefaultMargin,
                            });
                            if (_align == 'center') {
                                $img.css({
                                    'float': 'none',
                                    marginBottom: '',
                                    marginTop: '',
                                    maxWidth: 'calc(100% - ' + 2 * editor.opts.imageDefaultMargin + 'px)',
                                    textAlign: 'center',
                                });
                            }
                            else if (_align == 'left') {
                                $img.css({
                                    'float': 'left',
                                    marginLeft: 0,
                                    maxWidth: 'calc(100% - ' + editor.opts.imageDefaultMargin + 'px)',
                                    textAlign: 'left',
                                });
                            }
                            else {
                                $img.css({
                                    'float': 'right',
                                    marginRight: 0,
                                    maxWidth: 'calc(100% - ' + editor.opts.imageDefaultMargin + 'px)',
                                    textAlign: 'right',
                                });
                            }
                        }
                        else if (_display == 'block') {
                            $img.css({
                                display: 'block',
                                'float': 'none',
                                verticalAlign: 'top',
                                margin: editor.opts.imageDefaultMargin + 'px auto',
                                textAlign: 'center',
                            });
                            if (_align == 'left') {
                                $img.css({
                                    marginLeft: 0,
                                    textAlign: 'left',
                                });
                            }
                            else if (_align == 'right') {
                                $img.css({
                                    marginRight: 0,
                                    textAlign: 'right',
                                });
                            }
                        }
                    }
                    editor.edit.on();
                    editor.events.focus(true);
                    editor.selection.restore();
                    editor.undo.saveStep();
                    if (editor.opts.imageSplitHTML) {
                        editor.markers.split();
                    }
                    else {
                        editor.markers.insert();
                    }
                    editor.html.wrap();
                    var $marker = editor.$el.find('.fr-marker');
                    if ($marker.length) {
                        if ($marker.parent().is('hr')) {
                            $marker.parent().after($marker);
                        }
                        if (editor.node.isLastSibling($marker) && $marker.parent().hasClass('fr-deletable')) {
                            $marker.insertAfter($marker.parent());
                        }
                        $marker.replaceWith($img);
                    }
                    else {
                        editor.$el.append($img);
                    }
                    editor.selection.clear();
                    return $img;
                }
            }
        }
        wysiwyg.Editor = Editor;
    })(wysiwyg = modules.wysiwyg || (modules.wysiwyg = {}));
})(modules || (modules = {}));
