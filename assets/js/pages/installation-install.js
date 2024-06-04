import $ from 'jquery';
import Routing from 'fos-router';
import Translator from 'translator';
import { startsWith } from 'lodash';

const iconBusy = '<i class="fas fa-circle-notch fa-spin"></i>',
    iconSuccess = '<i class="fas fa-check text-success"></i>',
    iconFail = '<i class="fas fa-times text-danger"></i>';

function ajaxStep (action, callback) {
    const $step = $('#step-' + action),
        $container = $('<div />', { 'class': 'float-right icon' }),
        $clone = $container.clone();

    $step.append($clone.append(iconBusy));

    const route = ( startsWith(window.location.pathname, '/app.php') ? '/app.php' : '' ) + Routing.generate('_install_install');

    $.post(route, { 'action': action }).done((response) => {
        if (true === response.success) {
            $clone.remove();
            $step.append($container.append(iconSuccess));

            if (undefined !== callback) {
                callback();
            }
        } else {
            $clone.remove();
            $step.append($container.append(iconFail));
            $('#error-message').append(response.message || 'An unknown error occurred');
        }
    })
        .fail(function(jqXHR) {
            $clone.remove();
            $step.append($container.append(iconFail));
            $('#error-message').append(jqXHR.statusText);
        });
}

ajaxStep('createdb', () => {
    ajaxStep('migrations', () => {
        $('.progress').remove();
        $('#install-title').text(Translator.trans('installation.process.title.done'));
        $('#continue_step').removeClass('disabled');
    });
});
