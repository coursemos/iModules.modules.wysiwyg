/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/scripts/Wysiwyg.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
namespace modules {
    export namespace wysiwyg {
        export class Wysiwyg extends Module {
            static editors: WeakMap<HTMLTextAreaElement, modules.wysiwyg.Editor> = new WeakMap();

            /**
             * 모듈의 DOM 이벤트를 초기화한다.
             * 해당 DOM 내부에 에디터를 사용하는 <textarea> 필드가 존재할 경우
             * 에디터를 활성화한다.
             *
             * @param {Dom} $dom - 모듈 DOM 객체
             */
            init($dom: Dom) {
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
            getEditor($textarea: Dom): modules.wysiwyg.Editor {
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

        export namespace Editor {
            export interface Properties {
                [key: string]: any;
            }
        }

        export declare class FroalaEditor {
            public constructor(textarea: HTMLElement, options: modules.wysiwyg.Editor.Properties);
            public render(): Promise<any>;
            public get(): any;
            public $get(): any;
            public $: any;
        }

        export class Editor {
            id: string;
            $textarea: Dom;
            editor: modules.wysiwyg.FroalaEditor;
            uploader: modules.attachment.Uploader;

            /**
             * <textarea> DOM 객체를 이용하여 에디터를 활성화한다.
             *
             * @param {Dom} $textarea - <textarea> DOM 객체
             * @param {modules.wysiwyg.Editor.Properties} properties - 설정 (DOM 객체에 설정된 값보다 우선시 됩니다.)
             */
            constructor($textarea: Dom, properties: modules.wysiwyg.Editor.Properties = null) {
                this.$textarea = $textarea;

                this.id = this.$textarea.getAttr('data-id');

                properties ??= {};
                properties.scrollableContainer = 'div[data-module=wysiwyg]';
                properties.tooltips = false;
                properties.toolbarSticky = false;
                properties.toolbarButtons =
                    properties.toolbarButtonsMD =
                    properties.toolbarButtonsSM =
                    properties.toolbarButtonsXS =
                        [
                            'html',
                            '|',
                            'bold',
                            'underline',
                            'fontOptions',
                            'color',
                            'paragraphFormat',
                            '|',
                            'Hr',
                            'align',
                            'formatOL',
                            'formatUL',
                            'quote',
                            '|',
                            'insertLink',
                            'insertTable',
                            'insertImage',
                            'insertVideo',
                            'insertFile',
                            'emoticons',
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
                if ($textarea.getAttr('data-uploader-id')) {
                    const attachment = Modules.get('attachment') as modules.attachment.Attachment;
                    this.uploader = attachment.getUploader(
                        Html.get('div[data-role=uploader][data-id="' + $textarea.getAttr('data-uploader-id') + '"]')
                    );
                    this.uploader.setEditor(this);
                }

                this.editor.render().then(($editor) => {
                    if ($editor === null) {
                        return;
                    }

                    this.uploader.addEvent('update', (file: modules.attachment.Uploader.File) => {
                        const selector = 'img[data-index="' + file.index + '"], a[data-index="' + file.index + '"]';
                        const $placeholder = this.editor.$(selector, this.editor.get().$el);
                        if ($placeholder.length == 1) {
                            $placeholder.attr('data-attachment-id', file.attachment.id ?? 'UPLOADING');

                            if (file.status == 'COMPLETE') {
                                if ($placeholder.is('img') == true) {
                                    $placeholder.attr('src', file.attachment.view);
                                    this.editor.get().image.insert(
                                        file.attachment.view,
                                        false,
                                        {
                                            'attachment-id': file.attachment.id,
                                        },
                                        $placeholder
                                    );
                                } else {
                                    $placeholder.replaceWith(this.getFileLink(file));
                                    this.editor.get().edit.on();
                                }
                            }
                        }
                    });

                    $editor.on(
                        'froalaEditor.image.beforeUpload',
                        (_e: any, editor: any, files: FileList, $image_placeholder: any) => {
                            if (files.length == 0) {
                                return false;
                            }

                            const attachments = this.uploader.add(files);
                            for (const attachment of attachments) {
                                const $img = this.addImagePlaceholder(attachment, editor, $image_placeholder);
                                $img.addClass('fr-uploading');
                                if ($img.next().is('br')) {
                                    $img.next().remove();
                                }

                                editor.placeholder.refresh();
                                editor.edit.off();
                            }
                            return false;
                        }
                    );

                    $editor.on('froalaEditor.file.beforeUpload', (_e: any, editor: any, files: FileList) => {
                        if (files.length == 0) {
                            return false;
                        }

                        editor.edit.off();
                        editor.events.focus(true);
                        editor.selection.restore();

                        const attachments = this.uploader.add(files);
                        for (const attachment of attachments) {
                            const placeholder = this.getFileLink(attachment);
                            editor.html.insert(placeholder);
                            editor.placeholder.refresh();
                            editor.popups.hideAll();
                        }

                        return false;
                    });
                });
            }

            /**
             * 에디터 고유값을 가져온다.
             *
             * @return {string} id
             */
            getId(): string {
                return this.id;
            }

            /**
             * 에디터에 포함된 업로더를 가져온다.
             *
             * @return {modules.attachment.Uploader} uploader
             */
            getUploader(): modules.attachment.Uploader {
                return this.uploader;
            }

            /**
             * 파일 다운로드 링크 HTML 을 가져온다.
             *
             * @param {modules.attachment.Uploader.File} file
             * @return {string} html
             */
            getFileLink(file: modules.attachment.Uploader.File): string {
                const attributes: { [key: string]: string } = {
                    src: '',
                    'data-attachment-id': file.attachment.id ?? 'UPLOADING',
                    'data-module': 'attachment',
                    'contenteditable': 'false',
                    download: '',
                };

                if (file.status == 'COMPLETE') {
                    attributes.src = file.attachment.download;
                    attributes.download = file.attachment.name;
                    attributes['class'] = 'fr-deletable';
                } else {
                    attributes['data-index'] = file.index.toString();
                    attributes['class'] = 'fr-uploading fr-deletable';
                }

                const $link = Html.create('a', attributes);
                $link.append(
                    Html.create(
                        'i',
                        { 'data-type': file.attachment.type, 'data-extension': file.attachment.extension },
                        file.attachment.extension
                    )
                );
                $link.append(Html.create('span', null, file.attachment.name));
                $link.append(Html.create('small', null, Format.size(file.attachment.size)));

                return $link.toHtml() + '&nbsp;';
            }

            /**
             * 파일을 삽입한다.
             *
             * @param {modules.attachment.Uploader.File} file
             */
            insertFile(file: modules.attachment.Uploader.File): void {
                if (['image', 'svg', 'icon'].includes(file.attachment.type) == true) {
                    this.editor.get().image.insert(file.attachment.view, false, {
                        'attachment-id': file.attachment.id,
                    });
                } else {
                    this.editor.get().html.insert(this.getFileLink(file));
                }
            }

            /**
             * 에디터 콘텐츠 내용을 가져온다.
             *
             * @return {string} content
             */
            getContent(): string {
                if (this.editor.$get() === null) {
                    return this.$textarea.getValue();
                }

                if (this.editor.$get().froalaEditor('codeView.isActive') === true) {
                    this.editor.$get().froalaEditor('codeView.toggle');
                }

                return this.editor.$get().froalaEditor('html.get');
            }

            /**
             * 첨부파일을 가져온다.
             *
             * @return {string[]} attachment_ids - 첨부파일 고유값
             */
            getAttachments(): string[] {
                return this.uploader.getValue();
            }

            /**
             * 에디터 콘텐츠 내용을 가져온다.
             *
             * @return {Object} data
             */
            getValue(): {
                id: string;
                content: string;
                attachments: string[];
            } {
                if (this.isEmpty() == true && this.getAttachments().length == 0) {
                    return null;
                }

                return {
                    id: this.id,
                    content: this.getContent(),
                    attachments: this.getAttachments(),
                };
            }

            /**
             * 에디터 콘텐츠 내용을 설정한다.
             *
             * @param {Object} data
             */
            setValue(data: { content?: string; attachments?: string[] } = null): void {
                this.editor.$get().froalaEditor('html.set', data?.content ?? '');
                this.uploader.setValue(data?.attachments ?? []);
            }

            /**
             * 본문이 비었는지 확인한다. P, BR 태그 및 공백등을 제거하여 실제 데이터가 존재하는지 확인한다.
             *
             * @return {boolean} is_empty
             */
            isEmpty(): boolean {
                return (
                    this.getContent()
                        .replace(/<\/?(p|br|span)[^>]*>/gi, '')
                        .trim().length == 0
                );
            }

            /**
             * 이미지를 업로드하고 있는 도중 에디터에 업로드중인 이미지의 Placeholder 를 추가한다.
             *
             * @param {modules.attachment.Uploader.File} image - 업로드할 이미지파일 객체
             * @param {any} editor - 이미지가 업로드되는 에디터 객체
             */
            addImagePlaceholder(image: modules.attachment.Uploader.File, editor: any, $image_placeholder: any): any {
                if ($image_placeholder) {
                    $image_placeholder.attr('data-attachment-id', image.attachment.id);
                    $image_placeholder.attr('data-index', image.index);
                    editor.edit.on();
                    editor.undo.saveStep();

                    $image_placeholder.data('fr-old-src', $image_placeholder.attr('src'));
                    $image_placeholder.attr('src', image.attachment.view);

                    return $image_placeholder;
                } else {
                    const $image = Html.create('img', {
                        src: image.attachment.view,
                        'data-attachment-id': image.attachment.id,
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
                    } else {
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
                            } else if (_align == 'left') {
                                $img.css({
                                    'float': 'left',
                                    marginLeft: 0,
                                    maxWidth: 'calc(100% - ' + editor.opts.imageDefaultMargin + 'px)',
                                    textAlign: 'left',
                                });
                            } else {
                                $img.css({
                                    'float': 'right',
                                    marginRight: 0,
                                    maxWidth: 'calc(100% - ' + editor.opts.imageDefaultMargin + 'px)',
                                    textAlign: 'right',
                                });
                            }
                        } else if (_display == 'block') {
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
                            } else if (_align == 'right') {
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
                    } else {
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
                    } else {
                        editor.$el.append($img);
                    }

                    editor.selection.clear();

                    return $img;
                }
            }
        }
    }
}
