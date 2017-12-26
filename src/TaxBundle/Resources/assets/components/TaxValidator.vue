<template>
    <span>
        <a @click="validate" class="btn btn-default" rel="tooltip" title="EU Countries Only">
            <span v-if="!validating">
                tax.form.input.validate
            </span>
            <i class="fa fa-spin fa-refresh" v-if="validating"></i>
        </a>
    </span>
</template>

<script>
    import jQuery from 'jquery';
    import Router from '@SolidInvoiceCoreBundle/Resources/assets/js/router';

    export default {
        props: {
            field: {type: String, required: true}
        },
        data() {
            return {
                validating: false
            };
        },
        methods: {
            validate() {
                this.validating = true;

                jQuery.ajax({
                    'url': Router.generate('_tax_number_validate'),
                    'data': {'vat_number': jQuery('#' + this.field).val()},
                    'method': 'POST'
                }).done((result) => {
                    jQuery('#' + this.field)
                        .closest('.form-group')
                        .removeClass('has-success has-error')
                        .addClass(result.valid ? 'has-success' : 'has-error');
                }).always(() => {
                    this.validating = false;
                });
            }
        }
    }
</script>