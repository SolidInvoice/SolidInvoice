import $ from 'jquery';
import Router from 'router';
import Translator from 'translator';

const iconBusy = '<i class="fa fa-circle-o-notch fa-spin"></i>',
    iconSuccess = '<i class="fa fa-check text-success"></i>',
    iconFail = '<i class="fa-times text-danger"></i>';

function ajaxStep (action, callback) {
    const step = $('#step-' + action),
        container = $('<div />', { 'class': 'pull-right icon' }),
        clone = container.clone();

    step.append(clone.append(iconBusy));

    $.post(Router.generate('_install_install'), { 'action': action }).done((response) => {
        if (true === response.success) {
            clone.remove();
            step.append(container.append(iconSuccess));

            if (undefined !== callback) {
                callback();
            }
        } else {
            clone.remove();
            step.append(container.append(iconFail));
            $('#error-message').append(response.message || 'An unknown error occurred');
        }
    })
        .fail(function(jqXHR) {
            clone.remove();
            step.append(container.append(iconFail));
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
