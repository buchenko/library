/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
const $ = require('jquery');
require('bootstrap');
import 'content-editable/dist/content-editable';

const editables = document.querySelectorAll('content-editable');
editables.forEach(function (editable) {
    editable.addEventListener('edit', (e) => {
        e.preventDefault();
        $.post(e.target.dataset.url,
            {
                'field': e.target.dataset.field,
                'value': e.target.innerHTML
            })
            .done(function (data, textStatus, jqXHR) {
                e.target.dataset.value = e.target.innerHTML;
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                e.target.innerHTML = e.target.dataset.value;
                alert(jqXHR.responseText);
            });
    });
});
