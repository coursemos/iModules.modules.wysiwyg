/**
 * 이 파일은 아이모듈 위지윅에디터모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/scripts/Wysiwyg.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
var modules;
(function (modules) {
    let wysiwyg;
    (function (wysiwyg) {
        class Wysiwyg extends Module {
            init() {
                // @todo 에디터 초기화
                console.log('Editor INIT!', this.$dom);
                if (this.$dom !== null) {
                    console.log(this.$dom);
                }
            }
        }
        wysiwyg.Wysiwyg = Wysiwyg;
    })(wysiwyg = modules.wysiwyg || (modules.wysiwyg = {}));
})(modules || (modules = {}));
