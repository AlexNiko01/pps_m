document.addEventListener('DOMContentLoaded', function () {
    let getContent = document.getElementsByClassName('get-content');

    function init() {
        for (let i = 0; i <= getContent.length; i++) {
            if (!getContent[i]) {
                continue;
            }
            let apiContent = document.getElementById('apiContent');
            getContent[i].addEventListener('click', (e) => {
                e.preventDefault();
                let src = getContent[i].getAttribute('href');
                sendRequest(src).then((res) => {
                    apiContent.innerHTML = '';
                    apiContent.innerHTML = res;
                    init();
                }).catch(function (error) {
                    console.log(error);
                });
            }, false);
        }
    }

    function sendRequest(src) {
        return new Promise(function (resolve, reject) {
            let xhr = new XMLHttpRequest();
            xhr.open('GET', src, true);
            xhr.send();

            xhr.addEventListener('load', () => {
                if (xhr.response === 404) {
                    reject('error');
                } else {
                    let result = xhr.response;
                    resolve(result);
                }
            });
        });
    }

    init();
})
;
