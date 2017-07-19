define(['jquery', 'routing'], ($, Routing) => {
    return {
        attach(element, input) {
            let $el = $(element),
                $input = $(input);

            $input.on('change', () => {
                if ('' === $input.val()) {
                    $input
                        .closest('.form-group')
                        .removeClass('has-success has-error')
                }
            });

            $el.on('click', (e) => {
                e.preventDefault();
                let original = $el.html();

                $el.html('<i class="fa fa-spin fa-refresh"></i>');

                $.ajax({
                    'url' : Routing.generate('_tax_number_validate'),
                    'data': {'vat_number': $input.val()},
                    'method': 'POST'
                }).done((result) => {
                    $input
                        .closest('.form-group')
                        .removeClass('has-success has-error')
                        .addClass(result.valid ? 'has-success' : 'has-error');
                }).always(() => {
                    $el.html(original);
                });
            });
        }
    };
});