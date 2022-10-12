<template>
    <div class="text-center text-info">
        <h3>
            {{ creditValue }}
        </h3>
        <a href="#" @click.prevent="showModal = true" rel="tooltip" :title="Translator.trans('client.view.actions.add_credit')">
            <i class="fas fa-plus-circle"></i> {{ Translator.trans('client.stats.credit') }}
        </a>

        <b-modal
            v-model="showModal"
            :title="Translator.trans('client.modal.add_credit')"
            cancel-variant="warning"
            cancel-title="Close"
            ok-title="Save"
            ok-variant="success"
            @ok="addCredit"
        >
            <div class="row-fluid">
                <form id="credit-form">
                    <div class="form-group">
                        <label class="control-label required control-label required">{{ Translator.trans('client.modal.credit.amount') }}</label>
                        <div class="input-group">
                            <b-input-group :prepend="currency">
                                <b-form-input v-model="creditAmount"></b-form-input>
                            </b-input-group>
                            <span class="input-group-addon">R</span>
                        </div>
                        <small>
                            <span class="help-block" v-html="Translator.trans('client.modal.credit.tip', { amount: Accounting.formatNumber(-20, Accounting.settings.currency.precision, Accounting.settings.currency.thousand, Accounting.settings.currency.decimal) })"></span>
                        </small>
                    </div>
                </form>
            </div>

        </b-modal>
    </div>
</template>

<script setup>
    import Accounting from 'accounting';
    import Translator from 'translator';
    import Router from 'router';
    import { ref } from 'vue';
    import { $fetch } from 'ohmyfetch';
    import { BModal, BInputGroup, BFormInput } from 'bootstrap-vue';

    const props = defineProps({
        credit: {
            type: String,
            required: true,
        },
        clientId: {
            type: Number,
            required: true,
        },
        currency: {
            type: String,
            required: true,
        },
    });

    const creditValue = ref(props.credit);
    const showModal = ref(false);
    const creditAmount = ref(0.00);

    const addCredit = async () => {
        const response = await $fetch(
            Router.generate('_xhr_clients_credit_update', { client: props.clientId }),
            {
                method: 'PUT',
                body: {
                    credit: creditAmount.value,
                },
            }
        );

        showModal.value = false;
        creditValue.value = Accounting.formatMoney(response.credit);
        creditAmount.value = 0.00;
    };
</script>
