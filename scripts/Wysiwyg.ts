/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 위지윅에디터모듈 자바스크립트 클래스를 정의한다.
 *
 * @file /modules/wysiwyg/scripts/Wysiwyg.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
Modules.set(
    class wysiwyg extends Module {
        init() {
            console.log('Editor INIT!', this.dom);
            if (this.dom !== null) {
                console.log(this.dom);
            }
        }
    }
);
