document.addEventListener('DOMContentLoaded', function () {
    let getContent = document.getElementsByTagName('a');

    function init() {
        for (let i = 0; i <= getContent.length; i++) {
            if (!getContent[i]) {
                continue;
            }
            let apiContent = document.getElementById('apiContent');
            getContent[i].addEventListener('click', (e) => {
                e.preventDefault();
                if (getContent[i].getAttribute('href').indexOf('#') !== -1) {
                    return true;
                }

                let src = '/docs_dist/' + getContent[i].getAttribute('href');
                sendRequest(src).then((res) => {
                    let newContent = res.getElementsByClassName('api-content')[0].innerHTML;
                    apiContent.innerHTML = '';
                    apiContent.innerHTML = newContent;
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
            xhr.responseType = "document";
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
